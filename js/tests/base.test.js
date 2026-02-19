/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

// Read the file content
const baseJsPath = path.resolve(__dirname, '../base.js');
const baseJsContent = fs.readFileSync(baseJsPath, 'utf8');

describe('center_window', () => {
    let originalWindow;

    beforeAll(() => {
        // Mock navigator if needed
        Object.defineProperty(global, 'navigator', {
            value: { userAgent: 'Mozilla/5.0 (Node.js)' },
            writable: true
        });

        // Evaluate the script in the global context
        // We use eval to execute the script content in the current scope
        // This makes functions like center_window available globally
        try {
            window.eval(baseJsContent);
        } catch (e) {
            console.error('Error evaluating base.js:', e);
        }
    });

    beforeEach(() => {
        // Mock window properties needed for center_window
        // These are read-only by default in JSDOM, so we use Object.defineProperty
        Object.defineProperty(window, 'outerWidth', { value: 1024, configurable: true });
        Object.defineProperty(window, 'outerHeight', { value: 768, configurable: true });
        Object.defineProperty(window, 'screenX', { value: 100, configurable: true });
        Object.defineProperty(window, 'screenY', { value: 100, configurable: true });
    });

    test('centers window correctly with valid dimensions', () => {
        const width = 500;
        const height = 300;

        // Expected calculation:
        // mx = 100 + (1024 / 2) - (500 / 2) = 100 + 512 - 250 = 362
        // my = 100 + (768 / 2) - (300 / 2) = 100 + 384 - 150 = 334

        const result = window.center_window(width, height);

        // Note: The function returns a string with window features
        // The order of features in the string is: screenX, screenY, outerHeight, outerWidth
        const expected = 'screenX=362,screenY=334,outerHeight=300,outerWidth=500';
        expect(result).toBe(expected);
    });

    test('uses parent dimensions when width is <= 0', () => {
        const width = 0;
        const height = 300;

        // width <= 0 -> width = ix (1024), cx = mx (100)
        // height > 0 -> cy = my (334)

        const result = window.center_window(width, height);

        const expected = 'screenX=100,screenY=334,outerHeight=300,outerWidth=1024';
        expect(result).toBe(expected);
    });

    test('uses parent dimensions when height is <= 0', () => {
        const width = 500;
        const height = 0;

        // width > 0 -> cx = mx (362)
        // height <= 0 -> height = iy (768), cy = my (100)

        const result = window.center_window(width, height);

        const expected = 'screenX=362,screenY=100,outerHeight=768,outerWidth=500';
        expect(result).toBe(expected);
    });

    test('uses parent dimensions when both dimensions are <= 0', () => {
        const width = -1;
        const height = -1;

        // width <= 0 -> width = 1024, cx = 100
        // height <= 0 -> height = 768, cy = 100

        const result = window.center_window(width, height);

        const expected = 'screenX=100,screenY=100,outerHeight=768,outerWidth=1024';
        expect(result).toBe(expected);
    });

    test('handles floating point dimensions', () => {
        const width = 500.5;
        const height = 300.5;

        // mx = 100 + (1024 / 2) - (500.5 / 2) = 100 + 512 - 250.25 = 361.75 -> round -> 362
        // my = 100 + (768 / 2) - (300.5 / 2) = 100 + 384 - 150.25 = 333.75 -> round -> 334

        const result = window.center_window(width, height);

        const expected = 'screenX=362,screenY=334,outerHeight=300.5,outerWidth=500.5';
        expect(result).toBe(expected);
    });
});
