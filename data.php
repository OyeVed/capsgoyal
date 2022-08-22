<?php
// A PHP program to sort an array according
// to the order defined by another array
 
/* A Binary Search based function to find index
of FIRST occurrence of x in arr[]. If x is not
present, then it returns -1 */
function first(&$arr, $low, $high, $x, $n)
{
    if ($high >= $low)
    {
        $mid = intval($low + ($high - $low) / 2);
        if (($mid == 0 || $x > $arr[$mid - 1]) &&
                               $arr[$mid] == $x)
            return $mid;
        if ($x > $arr[$mid])
            return first($arr, ($mid + 1), $high, $x, $n);
        return first($arr, $low, ($mid - 1), $x, $n);
    }
    return -1;
}
 
// Sort A1[0..m-1] according to the order
// defined by A2[0..n-1].
function sortAccording(&$A1, &$A2, $m, $n)
{
    // The temp array is used to store a copy
    // of A1[] and visited[] is used mark the
    // visited elements in temp[].
    $temp = array_fill(0, $m, NULL);
    $visited = array_fill(0, $m, NULL);
    for ($i = 0; $i < $m; $i++)
    {
        $temp[$i] = $A1[$i];
        $visited[$i] = 0;
    }
 
    // Sort elements in temp
    sort($temp);
 
    $ind = 0; // for index of output which is sorted A1[]
 
    // Consider all elements of A2[], find
    // them in temp[] and copy to A1[] in order.
    for ($i = 0; $i < $n; $i++)
    {
        // Find index of the first occurrence
        // of A2[i] in temp
        $f = first($temp, 0, $m - 1, $A2[$i], $m);
 
        // If not present, no need to proceed
        if ($f == -1) continue;
 
        // Copy all occurrences of A2[i] to A1[]
        for ($j = $f; ($j < $m &&
             $temp[$j] == $A2[$i]); $j++)
        {
            $A1[$ind++] = $temp[$j];
            $visited[$j] = 1;
        }
    }
 
    // Now copy all items of temp[] which
    // are not present in A2[]
    for ($i = 0; $i < $m; $i++)
        if ($visited[$i] == 0)
            $A1[$ind++] = $temp[$i];
}
 
// Utility function to print an array
function printArray(&$arr, $n)
{
    for ($i = 0; $i < $n; $i++)
        echo $arr[$i] . " ";
    echo "\n";
}

$order = [
    1,
    51,
    207,
    208,
    4,
    209,
    211,
    210,
    2,
    3,
    5
];

$names = [
    'Not Started',
    'Not Started',
    'Backup NR',
    'Backup Recd',
    'In Progress',
    'Submitted',
    'Challan Pending',
    'Uploaded In Sw',
    'Feedback',
    'Testing',
    'Complete'
];

$DB_HOSTNAME = 'capsgoyal.com';
$DB_USERNAME = 'capsgr9s_excel';
$DB_PASSWORD = 'capsgr9s@@9911';
$DB_DBNAME = 'capsgr9s_goyal';

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
$status_heading_names = array();
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
    tbltasks.status,
    tblsi_custom_status.name AS 'name1',
    tbltickets_status.name AS 'name2'
    FROM tbltasks
    LEFT JOIN tblsi_custom_status ON tblsi_custom_status.id = tbltasks.status
    LEFT JOIN tbltickets_status ON tbltickets_status.ticketstatusid = tbltasks.status
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

            $heading_name = '';
            if($inner_row['name1'] != NULL){
                $heading_name = $inner_row['name1'];
            }elseif($inner_row['name2'] != NULL){
                $heading_name = $inner_row['name2'];
            }
            array_push($status_heading_names, $heading_name);
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

print_r($status_headings);
print_r($status_heading_names);

// Driver Code
sortAccording($status_headings, $order, sizeof($status_headings), sizeof($order));

foreach ($status_headings as $key => $value) {
    $status_heading_names[$key] = $names[array_search($value, $order)];
}

header('Content-Type: application/json');
echo json_encode([
    "status_headings" => $status_headings,
    "status_heading_names" => $status_heading_names,
    "data" => $data
]);