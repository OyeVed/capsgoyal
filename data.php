<?php

$DB_HOSTNAME = 'capsgoyal.com';
$DB_USERNAME = 'capsgr9s_excel';
$DB_PASSWORD = 'capsgr9s@@9911';
$DB_DBNAME = 'capsgr9s_goyal';

$DB_HOSTNAME = 'localhost';
$DB_USERNAME = 'root';
$DB_PASSWORD = '';
$DB_DBNAME = 'capsgoyal';

$dbcon = new mysqli($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

$sql = "SELECT
tbltags.name AS 'tag',
CONCAT(
'(',
GROUP_CONCAT(
    tbltasks.id
),
')'
) AS 'ids',
COUNT(
tbltasks.id
) AS 'total'
FROM tbltaggables
JOIN tbltags ON tbltags.id = tbltaggables.tag_id
JOIN tbltasks ON tbltasks.id = tbltaggables.rel_id
GROUP BY tbltaggables.tag_id";

$result = $dbcon->query($sql);

$status_headings = array();
$data = array();

while($row = $result->fetch_assoc()){
    $data_element = array();

    $inner_sql = "SELECT
    JSON_OBJECT(
        tbltasks.status, COUNT(tbltasks.id)
    ) AS 'status'
    FROM tbltasks
    WHERE tbltasks.id IN $row[ids]
    GROUP BY tbltasks.status";

    $inner_result = $dbcon->query($inner_sql);

    $data_element['Tag'] = $row['tag'];
    $data_element['Grand Total'] = $row['total'];
    $data_element['Status_Collapsed'] = array();
    $data_element['Status_Expanded'] = array();
    $status_expanded_element = array();

    $inner_sql = "SELECT
    tbltasks.id,
    tbltasks.name,
    tbltasks.status
    FROM tbltasks
    WHERE tbltasks.id in $row[ids]";

    $inner_result = $dbcon->query($inner_sql);

    while($inner_row = $inner_result->fetch_assoc()){

        if(isset($data_element['Status_Collapsed'][$inner_row['status']])){
            $data_element['Status_Collapsed'][$inner_row['status']] += 1;
        }else{
            $data_element['Status_Collapsed'][$inner_row['status']] = 1;
        }

        if(!in_array($inner_row['status'], $status_headings) ){
            array_push($status_headings, $inner_row['status']);
        }

        if(isset($status_expanded_element[$inner_row['name']][$inner_row['status']])){
            $status_expanded_element[$inner_row['name']][$inner_row['status']] += 1;
        }else{
            $status_expanded_element[$inner_row['name']][$inner_row['status']] = 1;
        }
    }

    foreach ($status_expanded_element as $key => $value) {
        array_push($data_element['Status_Expanded'], [
            'Task' => $key,
            'Status' => $value
        ]);
    }
    
    array_push($data, $data_element);
}

header('Content-Type: application/json');
echo json_encode([
    "status_headings" => $status_headings,
    "data" => $data
]);