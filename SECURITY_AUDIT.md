# Security, Privacy, and Resilience Audit Report

**Target:** dotProject (Legacy LAMP Application)
**Auditor:** Principal Application Security Engineer and Cloud Resilience Architect
**Date:** 2026-05-22

---

## Executive Summary

This audit has identified multiple **Critical** and **High** severity vulnerabilities that expose the application to significant risk. The legacy nature of the codebase, characterized by a lack of modern security practices, makes it susceptible to a wide range of attacks. The most severe findings include pervasive SQL Injection vulnerabilities, exposure of sensitive data in transit, and weak password management.

Immediate mitigation of all **Critical** findings is strongly recommended to prevent unauthorized data access, system compromise, and service disruption.

---

## Findings

### 1. SQL Injection via Direct Variable Concatenation

*   **Severity:** `Critical`
*   **Location:**
    *   `includes/sendpass.php`, line 36
    *   `modules/admin/do_user_aed.php`, line 60
    *   *Note: This pattern is likely present in hundreds of locations across all modules where database queries are constructed.*
*   **Description:** The application frequently constructs SQL queries by directly concatenating user-supplied input (`$_POST`, `$_GET`, `$_REQUEST`) into query strings. The `db_escape()` function is used, but it is not a reliable defense against all forms of SQL injection and is applied inconsistently. An attacker can manipulate input parameters to alter the SQL logic, allowing them to bypass authentication, exfiltrate sensitive data (e.g., user credentials, project data), or modify database records.
    *   **Example from `sendpass.php`:**
        ```php
        $q->addWhere('user_username=\''.$checkusername.'\' AND LOWER(contact_email)=\''.$confirmEmail.'\'');
        ```
*   **Proposed Mitigation:** Refactor all database queries to use the parameterized query functionality provided by the `DBQuery` class. The class already supports parameter binding, but this feature is not used. All user input must be passed as a parameter, not concatenated into the query string.
    *   **Example Fix:**
        ```php
        // Before
        $q->addWhere('user_username=\''.$checkusername.'\'');

        // After
        $q->addWhere('user_username = ?', $checkusername);
        ```

### 2. Plaintext Transmission of New User Credentials

*   **Severity:** `Critical`
*   **Location:** `modules/admin/do_user_aed.php`, lines 110-128
*   **Description:** When a new user account is created and the "Notify User" option is checked, the system sends a welcome email containing the user's password in **plaintext**. This exposes the credential to anyone who can intercept the email (e.g., via man-in-the-middle attack) or access the user's inbox. This is a severe violation of data privacy and security best practices.
*   **Proposed Mitigation:** Instead of emailing the password, the system should email a single-use, expiring link to a page where the new user can set their own password. This eliminates the transmission of plaintext credentials entirely.

### 3. Insecure "Forgotten Password" Mechanism

*   **Severity:** `Critical`
*   **Location:** `includes/sendpass.php`, lines 20-71
*   **Description:** The "forgot password" functionality generates a new, temporary password and emails it to the user in **plaintext**. This shares the same risks as the new user notification. Furthermore, the new password is then stored in the database after being hashed with MD5, a cryptographically broken algorithm.
*   **Proposed Mitigation:** The "forgot password" flow must be redesigned. It should generate a secure, single-use, expiring token that is stored in the database and associated with the user. An email should be sent to the user with a link containing this token. When the user clicks the link, the application validates the token and allows them to set a new password directly, without ever emailing a password.

### 4. Use of a Broken Hashing Algorithm (MD5)

*   **Severity:** `High`
*   **Location:** `includes/sendpass.php`, line 60
*   **Description:** The application uses the `md5()` function to hash passwords before storing them in the database. MD5 is a cryptographically broken hashing algorithm that is highly susceptible to collision and rainbow table attacks. If the password hashes are exfiltrated, an attacker can quickly recover a significant portion of the original plaintext passwords.
*   **Proposed Mitigation:** Migrate from MD5 to a modern, strong, and salted password hashing algorithm like **Argon2** or **bcrypt**. PHP provides the native `password_hash()` and `password_verify()` functions for this purpose. A migration path should be implemented where existing MD5 hashes are re-hashed to the new format upon the user's next successful login.

### 5. Lack of Rate Limiting on Authentication Endpoints

*   **Severity:** `High`
*   **Location:** Application-wide, specifically login forms and the "forgot password" form.
*   **Description:** The application does not implement any form of rate limiting on authentication attempts (login, password reset). This makes it highly vulnerable to brute-force and credential stuffing attacks, where an attacker can make an unlimited number of attempts to guess user passwords.
*   **Proposed Mitigation:** Implement a strict rate-limiting policy on a per-IP basis for all authentication-related endpoints. For example, after 5-10 failed attempts from a single IP address within a short time frame, block further attempts from that IP for a period (e.g., 15 minutes). This can be implemented at the webserver/proxy level (e.g., using `fail2ban`) or within the application logic.

### 6. Dynamic File Inclusion from User Input

*   **Severity:** `High`
*   **Location:** `index.php`, line 247
*   **Description:** The application includes PHP files based on user-controlled request parameters. While there is an attempt to sanitize the filename with `$AppUI->checkFileName()`, this pattern is inherently dangerous and can lead to Local File Inclusion (LFI) or Remote File Inclusion (RFI) if the sanitization can be bypassed.
    *   **Example from `index.php`:**
        ```php
        // require('./dosql/' . $_REQUEST['dosql'] . '.php'); // Commented out, but the next line is active
        include_once('./dosql/' . $AppUI->checkFileName($_REQUEST['dosql']) . '.php');
        ```
*   **Proposed Mitigation:** Refactor the code to avoid dynamic `include` paths based on direct user input. Instead, use a mapping (e.g., a `switch` statement or an associative array) to validate the user's request against a whitelist of allowed files to be included.

### 7. Outdated and Vulnerable Dependencies

*   **Severity:** `High`
*   **Location:** `lib/` directory (adodb, smarty, xajax, etc.)
*   **Description:** The project includes very old, unmanaged third-party libraries directly in the `lib/` directory. These libraries (e.g., ADOdb, Smarty, Xajax, JpGraph) have not been updated in years and likely contain numerous publicly disclosed and unpatched vulnerabilities (CVEs). This represents a significant supply chain risk.
*   **Proposed Mitigation:** This is a difficult problem to solve without significant refactoring. A short-term mitigation is to manually audit the specific versions of each library and search for known CVEs, applying patches if available. The long-term, strategic solution is to migrate the project to use a modern dependency manager like **Composer** and update all libraries to their latest secure versions, which would likely require significant code changes.

### 8. Lack of Cross-Site Request Forgery (CSRF) Protection

*   **Severity:** `Medium`
*   **Location:** Application-wide on all state-changing actions (e.g., `do_user_aed.php`).
*   **Description:** The application does not appear to use anti-CSRF tokens. This means an attacker could craft a malicious website that, when visited by a logged-in administrator, forces their browser to submit a request to the dotProject instance to perform an action (e.g., create a new admin user, delete a project) without the administrator's knowledge or consent.
*   **Proposed Mitigation:** Implement a synchronized token pattern. On every page load containing a form, generate a unique, random token and embed it in a hidden form field. On form submission, validate that the submitted token matches the one stored in the user's session. This should be applied to all forms that perform state-changing actions.