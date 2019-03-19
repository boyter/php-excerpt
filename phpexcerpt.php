<?php

// find the locations of each of the words
// Nothing exciting here. The array_unique is required 
// unless you decide to make the words unique before passing in
function _extractLocations($words, $fulltext) {
	$locations = array();
	if (is_array($words) || is_object($words)) {
		foreach($words as $word) {
			$wordlen = strlen($word);
			$loc = stripos($fulltext, $word);
			while($loc !== FALSE) {
				$locations[0] = $loc;
				$loc = stripos($fulltext, $word, $loc + $wordlen);
			}
		}
	} else {
		$wordlen = strlen($words);
		$loc = stripos($fulltext, $words);
		while($loc !== FALSE) {
			$locations[0] = $loc;
			$loc = stripos($fulltext, $words, $loc + $wordlen);
		}
	}
	$locations = array_unique($locations);
	
	// If no words were found, show beginning of the fulltext
	if(empty ($locations)) $locations[0]=0;

	sort($locations);
	return $locations;
}
// Work out which is the most relevant portion to display
// This is done by looping over each match and finding the smallest distance between two found 
// strings. The idea being that the closer the terms are the better match the snippet would be. 
// When checking for matches we only change the location if there is a better match. 
// The only exception is where we have only two matches in which case we just take the 
// first as will be equally distant.
function _determineSnipLocation($locations, $prevcount) {
	// If we only have 1 match we dont actually do the for loop so set to the first
	$startpos = $locations[0];	
	$loccount = count($locations);
	$smallestdiff = PHP_INT_MAX;	

	// If we only have 2 skip as its probably equally relevant
	if(count($locations) > 2) {
		// skip the first as we check 1 behind
		for($i=1; $i < $loccount; $i++) { 
			if($i == $loccount-1) { // at the end
				$diff = $locations[$i] - $locations[$i-1];
			}
			else {
				$diff = $locations[$i+1] - $locations[$i];
			}
			
			if($smallestdiff > $diff) {
				$smallestdiff = $diff;
				$startpos = $locations[$i];
			}
		}
	}
		
	$startpos = $startpos > $prevcount ? $startpos - $prevcount : 0;
	return $startpos;
}

// 1/6 ratio on prevcount tends to work pretty well and puts the terms
// in the middle of the extract
function extractRelevant($words, $fulltext, $rellength=300, $prevcount=50, $indicator='...') {

	$textlength = strlen($fulltext);
	if($textlength <= $rellength) {
		return $fulltext;
	}
	
	$locations = _extractLocations($words, $fulltext);
	$startpos  = _determineSnipLocation($locations,$prevcount);

	// if we are going to snip too much...
	if($textlength-$startpos < $rellength) {
		$startpos = $startpos - ($textlength-$startpos)/2;
	}
	
	$reltext = substr($fulltext, $startpos, $rellength);
	
	// check to ensure we dont snip the last word if thats the match
	if( $startpos + $rellength < $textlength) {
		$reltext = substr($reltext, 0, strrpos($reltext, " ")).$indicator; // remove last word
	}
	
	// If we trimmed from the front add ...
	if($startpos != 0) {
		$reltext = $indicator.substr($reltext, strpos($reltext, " ") + 1); // remove first word
	}
	
	return $reltext;
}
?>
