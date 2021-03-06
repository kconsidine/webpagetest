<?php
include 'common.inc';
include 'page_data.inc';
include 'object_detail.inc';
require_once('testStatus.inc');
set_time_limit(300);

$testIds = array();

// Get Test IDs
if( isset($_REQUEST['tests']) && strlen($_REQUEST['tests']) )
{
    $testIds = explode(',', $_REQUEST['tests']);
}
// Get the list of tests to match with
$searches = array();
if( isset($_REQUEST['searches']) && strlen($_REQUEST['searches']) )
{
    $searches = explode(',', $_REQUEST['searches']);
}

header("Content-disposition: attachment; filename={$id}_headersMatch.csv");
header("Content-type: text/csv");
    
// list of metrics that will be produced
// for each of these, the median, average and std dev. will be calculated
echo "\"Test ID\",\"Found\"\r\n";
        
// and now the actual data
foreach( $testIds as &$testId )
{
	$cached = 0;
	
	RestoreTest($testId);
        GetTestStatus($testId);
        $testPath = './' . GetTestPath($testId);
        $pageData = loadAllPageData($testPath);
	$medianRun = GetMedianRun($pageData, $cached);

	$secured = 0;
	$haveLocations=1;
	$requests = getRequests($testId, $testPath, $medianRun, $cached, $secure, $haveLocations, false,true);

	// Flag indicating if we matched
	$matched = array();
	$nSearches = count($searches);
	foreach( $requests as &$r )
	{
                if( isset($r['headers']) && isset($r['headers']['response']) )
                {
                    foreach($r['headers']['response'] as &$header)
                    {
			// Loop through the search conditions we received
			for($i=0; $i<$nSearches; $i++)
			{
				// Skip already matched items
				if ($matched[$i])
					continue;
				if (strpos($header, $searches[$i]) !== false ) {
					$matched[$i] = "$i;";
				}
			}
		    }
		}
		// Optimization: IF we matched on all of them, no need to continue
		if (count($matched) == $nSearches) {
			break;
		}
	}
	// Write the results
	echo "\"$testId\",\"" . implode(array_values($matched)) . "\"\r\n";
}    

?>
