<?php

function getCostValueTotal($id) {
    $query = new DBQuery;
    $query->addTable('human_resource');
    $query->addQuery('*');
    $sql = $query->prepare();
    $query->clear();
    return db_loadList($sql);
}

function getResources($cond, $project) {
    $q = new DBQuery;
    if ($cond == "Human") {
        $q->clear();
        $q->addQuery('*');
        $q->addTable('costs');
        $q->addWhere("cost_type_id = '0' $project");
        $q->addOrder('cost_description');
        $humanCost = $q->loadList();
        return $humanCost;
    } else if ($cond == "Non-Human") {
        $q->clear();
        $q->addQuery('*');
        $q->addTable('costs');
        $q->addWhere("cost_type_id = '1' $project");
        $q->addOrder('cost_description');
        $notHumanCost = $q->loadList();
        return $notHumanCost;
    }
}

function diasemana($data) {
    $ano = substr("$data", 0, 4);
    $mes = substr("$data", 5, -3);
    $dia = substr("$data", 8, 9);

    $diasemana = date("w", mktime(0, 0, 0, $mes, $dia, $ano));

    switch ($diasemana) {
        case"0": $diasemana = "Domingo";
            break;
        case"1": $diasemana = "Segunda-Feira";
            break;
        case"2": $diasemana = "Terça-Feira";
            break;
        case"3": $diasemana = "Quarta-Feira";
            break;
        case"4": $diasemana = "Quinta-Feira";
            break;
        case"5": $diasemana = "Sexta-Feira";
            break;
        case"6": $diasemana = "Sábado";
            break;
    }

    echo "$diasemana";
}

function diferencaMeses($d1, $d2) {

    return diffDate($d1, $d2, 'M');
}

function insertCostValues($project) {
    // INSERT ON COSTS

    $q = new DBQuery();
    /*
      $q->clear();
      $q->addQuery('dp.user_id,dp.task_id,SUM((dp.perc_assignment/100) * t.task_duration) as hours_user');
      $q->addTable('tasks', 't');
      $q->innerJoin('user_tasks', 'dp', 'dp.task_id = t.task_id');
      $q->innerJoin('human_resource', 'h', 'dp.user_id = h.human_resource_user_id');
      $q->innerJoin('users', 'usr', 'usr.user_id = h.human_resource_user_id');
      $q->addWhere('t.task_project = ' . $project);
      $q->addGroup('usr.user_id');
      $q->addOrder('usr.user_id ASC');
      $hoursUser = $q->loadList();

      $k = 0;
      $i = 0;
      foreach ($hoursUser as $temp) {
      $array[$i] = $temp['hours_user'];
      $i++;
      }
     *  
      /* CRIAR ATUALIZAÇÃO CUSTOS HUMANOS E NÃO HUMANOS */
    /*
      $value = ($row['cost_value'] * $row['cost_quantity']) * diferencaMeses($date1, $date2);
      $q->clear();
      $q->addTable('costs');
      $q->addUpdate('cost_description', $row['contact_first_name'] . ' '. $row['contact_last_name']. ' - ' . $row['human_resources_role_name']);
      $q->addUpdate('cost_value_unitary', $row['cost_value']);
      $q->addUpdate('cost_value_total', $value);
      $q->addWhere('cost_id='. $row['cost_id'].' and cost_type_id= 0');
      $q->exec();
     * 
     */

    $q->clear();
    $q->addQuery('DISTINCT usr.user_username,hs.human_resources_role_name, usr.user_id, mc.cost_value,cts.contact_first_name,cts.contact_last_name');
    $q->addTable('tasks', 't');
    $q->innerJoin('user_tasks', 'dp', 'dp.task_id = t.task_id');
    $q->innerJoin('human_resource', 'h', 'dp.user_id = h.human_resource_user_id');
    $q->innerJoin('users', 'usr', 'usr.user_id = h.human_resource_user_id');
    $q->innerJoin('contacts', 'cts', 'usr.user_contact = cts.contact_id');
    $q->innerJoin('human_resource_roles', 'hr', 'hr.human_resource_id = h.human_resource_id');
    $q->innerJoin('human_resources_role', 'hs', 'hr.human_resources_role_id = hs.human_resources_role_id');
    $q->innerJoin('monitoring_user_cost', 'mc', 'usr.user_id = mc.user_id');
    $q->addWhere('t.task_project = ' . $project);
    $q->addOrder('usr.user_id ASC');
    $res = $q->loadList();


    $whereProject = ' and cost_project_id=' . $project;

    $humanCost = getResources("Human", $whereProject);

    $date1 = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
    $date2 = mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"));
    global $db;
    if ($humanCost == null) {
        $db->StartTrans();
        foreach ($res as $row) {

            $q->clear();
            $q->addTable('costs');
            $q->addInsert('cost_type_id', 0);
            $q->addInsert('cost_project_id', $project);
            $q->addInsert('cost_description', $row['contact_first_name'] . ' ' . $row['contact_last_name'] . ' - ' . $row['human_resources_role_name']);
            $q->addInsert('cost_date_begin', date('Y-m-d H:i:s', $date1));
            $q->addInsert('cost_date_end', date('Y-m-d H:i:s', $date2));
            $q->addInsert('cost_quantity', 1);
            $q->addInsert('cost_value_unitary', $row['cost_value']);
            $q->addInsert('cost_value_total', $row['cost_value']);
            $q->exec();
        }
        $db->CompleteTrans();
    } else {
        /* ################### UPDATE VALORES DOS CUSTOS HUMANOS #################### */
        $i = 0;
        foreach ($res as $row) {
            $k = 0;
            $array[$i][$k] = $row['cost_value'];
            $k = $k + 1;
            $array[$i][$k] = $row['contact_first_name'] . ' ' . $row['contact_last_name'] . ' - ' . $row['human_resources_role_name'];
            $i++;
        }
        $j = 0;
        $db->StartTrans();
        foreach ($humanCost as $row) {
            $l = 0;
            $value = ($array[$j][$l] * $row['cost_quantity']) * diferencaMeses(substr($row['cost_date_begin'], 0, -9), substr($row['cost_date_end'], 0, -9));
            $q->clear();
            $q->addTable('costs');
            $q->addUpdate('cost_value_unitary', $array[$j][$l]);
            $q->addUpdate('cost_value_total', $value);
            $l = $l + 1;
            $q->addWhere('cost_description="' . $array[$j][$l] . '" and cost_type_id= 0');
            $q->exec();
            $j++;
        }
        $db->CompleteTrans();
        $db->StartTrans();
        foreach ($res as $row) {
            $name = $row['contact_first_name'] . ' ' . $row['contact_last_name'] . ' - ' . $row['human_resources_role_name'];
            $bool = true;
            foreach ($humanCost as $column) {
                if ($name == $column['cost_description']) {
                    $bool = false;
                }
            }
            if ($bool == true) {
                $q->clear();
                $q->addTable('costs');
                $q->addInsert('cost_type_id', 0);
                $q->addInsert('cost_project_id', $project);
                $q->addInsert('cost_description', $name);
                $q->addInsert('cost_quantity', 1);
                $q->addInsert('cost_date_begin', date('Y-m-d H:i:s'));
                $q->addInsert('cost_date_end', date('Y-m-d H:i:s', $date2));
                $q->addInsert('cost_value_unitary', $row['cost_value']);
                $q->addInsert('cost_value_total', $row['cost_value']);
                $q->exec();
            }
        }
        $db->CompleteTrans();
    }

    $notHumanCost = getResources("Non-Human", $whereProject);

    $q->clear();
    $q->addQuery('r.resource_name,t.task_id,COUNT(r.resource_name) as qntd');
    $q->addTable('tasks', 't');
    $q->innerJoin('resource_tasks', 'rt', 'rt.task_id = t.task_id');
    $q->innerJoin('resources', 'r', 'r.resource_id = rt.resource_id');
    $q->addWhere('t.task_project = ' . $project);
    $q->addWhere('rt.percent_allocated = 100');
    $q->addGroup('r.resource_name');
    $q->addOrder('r.resource_name ASC');
    $resNH = $q->loadList();


    if ($notHumanCost == null) {
        $db->StartTrans();
        foreach ($resNH as $row) {

            $q->clear();
            $q->addTable('costs');
            $q->addInsert('cost_type_id', 1);
            $q->addInsert('cost_project_id', $project);
            $q->addInsert('cost_description', $row['resource_name']);
            $q->addInsert('cost_quantity', $row['qntd']);
            $q->addInsert('cost_date_begin', date('Y-m-d H:i:s'));
            $q->addInsert('cost_date_end', date('Y-m-d H:i:s', $date2));
            $q->addInsert('cost_value_unitary', 0);
            $q->addInsert('cost_value_total', 0);
            $q->exec();
        }
        $db->CompleteTrans();
    } else {
        /* ################### UPDATE OR INSERTE NON-HUMAN RESOURCES ######################## */
        $i = 0;
        foreach ($resNH as $row) {
            $k = 0;
            $array[$i][$k] = $row['qntd'];
            $k = $k + 1;
            $array[$i][$k] = $row['resource_name'];
            $i++;
        }

        $j = 0;

        $db->StartTrans();
        foreach ($notHumanCost as $row) {
            $l = 0;
            $value = $array[$j][$l] * $row['cost_value_unitary'];
            $q->clear();
            $q->addTable('costs');
            $q->addUpdate('cost_quantity', $array[$j][$l]);
            $q->addUpdate('cost_value_total', $value);
            $l = $l + 1;
            $q->addWhere('cost_description="' . $array[$j][$l] . '" and cost_type_id= 1');
            $q->exec();
            $j++;
        }
        $db->CompleteTrans();

        $db->StartTrans();
        foreach ($resNH as $row) {
            $bool = true;
            foreach ($notHumanCost as $column) {
                if ($row['resource_name'] == $column['cost_description']) {
                    $bool = false;
                }
            }
            if ($bool == true) {
                $q->clear();
                $q->addTable('costs');
                $q->addInsert('cost_type_id', 1);
                $q->addInsert('cost_project_id', $project);
                $q->addInsert('cost_description', $row['resource_name']);
                $q->addInsert('cost_quantity', $row['qntd']);
                $q->addInsert('cost_date_begin', date('Y-m-d H:i:s'));
                $q->addInsert('cost_date_end', date('Y-m-d H:i:s', $date2));
                $q->addInsert('cost_value_unitary', 0);
                $q->addInsert('cost_value_total', 0);
                $q->exec();
            }
        }
        $db->CompleteTrans();
    }
}

function insertReserveBudget($project) {

    $q = new DBQuery();

    $q->clear();
    $q->addQuery('r.risk_id,r.risk_name');
    $q->addTable('risks', 'r');
    $q->addWhere("risk_project = " . $project . " and risk_probability = '3' or risk_probability = '4'");
    $q->addOrder('risk_id');
    $risk = $q->loadList();


    $q->clear();
    $q->addQuery('*');
    $q->addTable('budget_reserve', 'b');
    $q->addWhere("budget_reserve_project_id = " . $project);
    $q->addOrder('budget_reserve_risk_id');
    $budgets = $q->loadList();


    global $db;
    if ($budgets == null) {
        $db->StartTrans();
        foreach ($risk as $row) {

            $q->clear();
            $q->addTable('budget_reserve');
            $q->addInsert('budget_reserve_project_id', $project);
            $q->addInsert('budget_reserve_risk_id', $row['risk_id']);
            $q->addInsert('budget_reserve_description', $row['risk_name']);
            $q->addInsert('budget_reserve_financial_impact', 0);
            $q->addInsert('budget_reserve_inicial_month', date('Y-m-d H:i:s'));
            $q->addInsert('budget_reserve_final_month', date('Y-m-d H:i:s'));
            $q->addInsert('budget_reserve_value_total', 0);
            $q->exec();
        }
        $db->CompleteTrans();
    } else {
        $db->StartTrans();
        foreach ($risk as $row) {
            $q->clear();
            $q->addTable('budget_reserve');
            $q->addUpdate('budget_reserve_description', $row['risk_name']);
            $q->addWhere('budget_reserve_project_id=' . $project . ' and budget_reserve_risk_id=' . $row['risk_id']);
            $q->exec();
        }
        $db->CompleteTrans();
        $db->StartTrans();
        foreach ($risk as $row) {
            $bool = true;
            foreach ($budgets as $column) {
                if ($row['risk_id'] == $column['budget_reserve_risk_id']) {
                    $bool = false;
                }
            }
            if ($bool == true) {
                $q->clear();
                $q->addTable('budget_reserve');
                $q->addInsert('budget_reserve_project_id', $project);
                $q->addInsert('budget_reserve_risk_id', $row['risk_id']);
                $q->addInsert('budget_reserve_description', $row['risk_name']);
                $q->addInsert('budget_reserve_financial_impact', 0);
                $q->addInsert('budget_reserve_inicial_month', date('Y-m-d H:i:s'));
                $q->addInsert('budget_reserve_final_month', date('Y-m-d H:i:s'));
                $q->addInsert('budget_reserve_value_total', 0);
                $q->exec();
            }
        }
        $db->CompleteTrans();
    }
}

function insertBudget($project, $subTotal) {

    $q = new DBQuery();
    $q->clear();
    $q->addQuery('*');
    $q->addTable('budget');
    $q->addWhere('budget_project_id = ' . $project);
    $q->addOrder('budget_id');
    $res = $q->loadList();
    $resul = $q->exec();

    if ($res == null) {

        $q->addTable('budget');
        $q->addInsert('budget_id', $project);
        $q->addInsert('budget_project_id', $project);
        $q->addInsert('budget_reserve_management', 0);
        $q->addInsert('budget_sub_total', $subTotal);
        $q->addInsert('budget_total', $subTotal);
        $q->exec();
    } else {
        $total = (($res[0]['budget_reserve_management'] / 100) * $subTotal);
        $total += $subTotal;

        $q->addTable('budget');
        $q->addUpdate('budget_sub_total', $subTotal);
        $q->addUpdate('budget_total', $total);
        $q->addWhere('budget_id=' . $project);
        $q->exec();
    }
}

function diffDate($d1, $d2, $type = '', $sep = '-') {
    $d1 = explode($sep, $d1);
    $d2 = explode($sep, $d2);
    switch ($type) {
        case 'A':
            $X = 31536000;
            break;
        case 'M':
            $X = 2592000;
            break;
        case 'D':
            $X = 86400;
            break;
        case 'H':
            $X = 3600;
            break;
        case 'MI':
            $X = 60;
            break;
        default:
            $X = 1;
    }


    return floor(((mktime(0, 0, 0, $d2[1], $d2[2], $d2[0])) - (mktime(0, 0, 0, $d1[1], $d1[2], $d1[0]))) / $X);
}

function subTotalBudget($meses, $c, $mtz, $control, $sumColumns) {
    for ($i = 0; $i <= $meses; $i++) {

        echo "<td nowrap='nowrap'>    <b>";

        for ($j = 0; $j <= $c; $j++) {
            $sum = $sum + $mtz[$j][$i];
        }
        $sumColumns[$control][$i] = $sum;

        echo number_format($sum, 2, ',', '.');

        echo " </b>   </td>";


        $sum = 0;
    }
    return $sumColumns;
}

function subTotalBudgetRow($meses, $c, $mtz, $control) {
    for ($i = 0; $i <= $meses; $i++) {
        $sum = $sum + $mtz[$control][$i];
    }
    return $sum;
}

function costsBudget($meses, $c, $row, $mStartProject, $mEndProject, $mtz) {

    $monthStart = substr($row['cost_date_begin'], 5, -12);
    $diffMonths = diferencaMeses(substr($row['cost_date_begin'], 0, -9), substr($row['cost_date_end'], 0, -9));


    if ($diffMonths < 0)
        $diffMonths = 0;


    for ($i = 0; $i <= $meses; $i++) {
        $temp = $mStartProject;
        $mStartProject++;

        if ($monthStart == $temp) {
            $k = $i;
            for ($j = 0; $j <= $diffMonths; $j++) {
                echo "<td nowrap='nowrap'>";
                $mtz[$c][$k] = $row['cost_value_total'] / ($diffMonths + 1);
                echo number_format($mtz[$c][$k], 2, ',', '.');
                echo "</td>";
                $k++;
            }
            $i = $k - 1;
        } else {
            echo "<td nowrap='nowrap'>";
            $mtz[$c][$i] = 0;
            echo number_format(0, 2, ',', '.');
            echo "</td>";
        }
    }

    return $mtz;
}

function costsContingency($meses, $c, $row, $mStartProject, $mEndProject, $mtz) {

    $monthStart = substr($row['budget_reserve_inicial_month'], 5, -12);
    $diffMonths = diferencaMeses(substr($row['budget_reserve_inicial_month'], 0, -9), substr($row['budget_reserve_final_month'], 0, -9));

    if ($diffMonths < 0)
        $diffMonths = 0;

    for ($i = 0; $i <= $meses; $i++) {
        $temp = $mStartProject;
        $mStartProject++;

        if ($monthStart == $temp) {
            $k = $i;
            for ($j = 0; $j <= $diffMonths; $j++) {
                echo "<td nowrap='nowrap'>";
                $mtz[$c][$k] = $row['budget_reserve_financial_impact'];
                echo number_format($mtz[$c][$k], 2, ',', '.');
                echo "</td>";
                $k++;
            }
            $i = $k - 1;
        } else {
            echo "<td nowrap='nowrap'>";
            $mtz[$c][$i] = 0;
            echo number_format(0, 2, ',', '.');
            echo "</td>";
        }
    }

    return $mtz;
}

function totalBudget($meses, $sumColumns) {

    for ($i = 0; $i <= $meses; $i++) {

        for ($j = 0; $j <= 2; $j++) {
            $result += $sumColumns[$j][$i];
        }
        echo "<td nowrap='nowrap' width='10%'>";
        echo "<b>";
        echo number_format($result, 2, ',', '.');

        echo "</b>";
        echo "</td>";
        $result = 0;
    }
}

?>
