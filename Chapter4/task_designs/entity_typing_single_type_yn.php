<!DOCTYPE html>
<html>
<head>
	<?php
		function createTypesCheckBox($entityUri, $type) {
			echo "<input type=\"checkbox\" name=\"{$entityUri}[]\" value=\"{$type['uri']}\"/>{$type['label']}<br />";
		}


		function partitionTypes($types) {
			$partitions = array();
			foreach($types as $type) {
				if( !array_key_exists($type['label'], $partitions) )
					$partitions[ $type['label']] = array();
				
				$partitions[ $type['label'] ][] = $type['uri'];
			}//for
			return $partitions;
		}//getSimilarTypes


		function types_uri_string($uris) {
			$r = "";
			foreach( $uris as $uri )
				$r = $r . "{$uri}\t";
			return $r;
		}

		function createTypesYesNoTable($entity, $types ) {
			echo "<br /><table>
			<tr> 
				<th colspan=\"2\" style=\"text-align: left\">{$entity['label']}</th> 
			</tr>";

			$types_to_print = partitionTypes($types);
			$i = 0; 
			// var_dump($types_to_print);
			foreach( $types_to_print as $label => $uris ) {
				$similar_types_string = types_uri_string($uris);
				echo "<tr> 
				<td>{$label}
					<input type=\"hidden\" name=\"{$entity['uri']}_{$i}_types\" value=\"{$similar_types_string}|\">
				</td>
				<td> 
					<input type=\"radio\" name=\"{$entity['uri']}_{$i}[]\" value=\"true\">yes
					<input type=\"radio\" name=\"{$entity['uri']}_{$i}[]\" value=\"false\">no
				</td>
				</tr>";
				$i++;
			}//for
			//creating text box to suggest a type
			echo "<tr>
			<td colspan=\"2\">Suggest a new type / signal not an entity: <input type=\"text\" name=\"{$entity['uri']}_suggestion\"></td>
			</tr>";
			echo "</table>";
		}//createTypesYesNoTable

		function cmp($a, $b) {
		    if( $a['offset'] == $b['offset'] ) return 0;
		    
		    return ($a['offset'] < $b['offset']) ? -1 : 1;
		}//cmp

		/** given a structure describing a task, prints the text highlighting the mentions of all
		* entities */
		function printArticle( $task ) {
			// use <font class="HL">this is highlighted</font> to highlight entity mentions.
			$entities = $task["entities"];
			$article = $task['article'];
			$allOffset = array();
			foreach( $entities as $entity ) {
				$allOffset = array_merge($allOffset, $entity['offsets']);
			}//for	
			// under the assumption that mentions don't have overlaps
			// order in decreasing order
			usort($allOffset, 'cmp');
			// var_dump($allOffset);
			// echo "<br/>";
			$currentPoint = 0;
			for( $i = 0; $i < count($allOffset); $i++ ) {
				// echo "<br />";
				// var_dump($allOffset[$i]);
				echo substr($article, $currentPoint, $allOffset[$i]['offset'] - $currentPoint);
				echo "<font class=\"HL\">" . substr($article, $allOffset[$i]['offset'], $allOffset[$i]['length']) . "</font>";
				$currentPoint = $allOffset[$i]['offset'] + $allOffset[$i]['length'];
			}//for
			echo substr($article, $currentPoint); 
		}//printArticle
	?>
	
	<?php

	//Amazon
	if(isset($_GET['assignmentId'])){
		$assignmentId = $_GET['assignmentId'];
	}
	else {
		$assignmentId ="";
	}

	//Amazon
	if(isset($_GET['workerId'])){
		$id = $_GET['workerId'];
	}
	
	//task	

	if(isset($_GET['snippet'])){
		$snippet = $_GET['snippet'];
	}
	else {
		$snippet = "";
	}
?>

<script type="text/javascript">
function ShowHideInstructions(){
	par = document.getElementById("Instructions");
	if(par.style.display == 'block' ){
		par.style.display = 'none';
		par.style.width = '500px';
	} else {
		par.style.display = 'block';
		par.style.width = '500px';
	}
}
</script>

<style type="text/css">
<!--
.HL	{	
	background: #ffff00;
	color: #000000;
}
-->
</style>

<title><?php echo $snippet; ?></title>

<script language="JavaScript" type="text/javascript">
<!--

/**
* Replaces all \t and \n with single spaces in every 
* textbox/textarea.
*/
function fixTexts(form) {
	for( var element = 0; element < form.elements.length; element++ ) {
		if( form.elements[element].type == "textarea" || 
			form.elements[element].type == "text" ) {

			form.elements[element].value = form.elements[element].value.replace(/([\n\t])+/g, " ");
		}//if
	}//for
}//fixTexts


function checkform (form) {
	/* every radio button has name <entity_uri>_n where n is an integer
	* the "yes" radio button as <type_uri> as value 
	* every text-box for type-suggestion has <entity_uri> as name and 
	* the suggested type (or error) as value. */

	//associative array mapping entity urls to types suggested by the user
	var entitySuggestedTypes = new Array();

	//for each entity gives the number of types selected with radio buttons
	var typesCount = new Array();
	for( element = 0; element < form.elements.length; element++ ) {
		if( form.elements[element].type == "radio" ) {
			if( !( form.elements[form.elements[element].name][0].checked || 
				form.elements[form.elements[element].name][1].checked) ) {
				alert("Error: at least one type has not been evaluated.");
				return false;
			}

			var lastIdx_ = form.elements[element].name.lastIndexOf("_");
			var entityUri = form.elements[element].name.substr(0, lastIdx_);
			
			if( typeof(typesCount[entityUri]) == "undefined" ) typesCount[entityUri] = 0;
			
			if(	form.elements[element].checked &&
				form.elements[element].value == "true" ) {
				typesCount[entityUri]++;
				// alert( "pushing type " + form.elements[element].value + " for " + entityUri);
			}//if
		}//if

		if( form.elements[element].type == "text" && form.elements[element].value != "" ) {
			var lastIdx_ = form.elements[element].name.lastIndexOf("_");
			var entityUri = form.elements[element].name.substr(0, lastIdx_);

			entitySuggestedTypes[entityUri] = form.elements[element].value;
			// alert("suggested type: " + entitySuggestedTypes[entityUri] + 
			// 	" for entity " + entityUri );
		}//if
	}//for

	// validation...
	for( var count in typesCount ) {
		if( (typesCount[count] == 0 && entitySuggestedTypes[count] != "error")
			|| typesCount[count] > 1 )  { 
			// alert( count + " has something wrong: " + typesCount[count]);
			alert("Please, check to have selected exactly one \"yes\" per entity or to have signaled an error " +
				"by using the procedure described in the instructions.");
			return false;
		}//if
	}//for
	
	fixTexts(form);
	return true;
	// var comments = document.getElementById("commentsarea").value;
	// var othercomments = comments.replace(/([\n\t])+/g, " ");
	// document.getElementById("commentsarea").value = othercomments;
}//checkform
-->

</script>
</head>


<body>
<?php
if( isset($_GET['docID']) ) {
	$docID = $_GET['docID'];
	
	$json = file_get_contents("$docID.json");
	$task = json_decode($json, true);
	if( $task == null ) {
		echo "JSON ERROR!";
	}
?>

<!--  -->

<h2>Select the most appropriate types for entities</h2>
<h3 style="color: orange; width: 500px;">This is a pilot task, so, feedback to improve its quality 
	to run it in large scale is highly appreciated.</h3>
<h3 style="color: red">Instructions</h3>
	<p style="width: 500px">Imagine you are reading the following news.</p>
	<p style="width: 500px">Please, select for each entity (persons/organizations/locations) 
		the description which best explains it with respect to the news. 
		Note that all the descriptions are correct, just select the <b>best</b> one.</p>
	<h4 onclick="ShowHideInstructions()" style="color: red; text-decoration: underline">Click for more/less info...</h4>
<div id="Instructions" style="width: 500px; display: none">
	<p>For example, if "Tom Cruise" appears in an article talking about Scientology then
	"Scientology adept" is a better description than "Actor".</p>
	<!-- <br />
	You can also propose a new type that fits better than all the listed ones by using
	the "Suggest a new type / signal not and entity" field. 
	<emph>In every case you have to say "yes" to at least one of the listed types</emph>. -->
	<p>If you think that one of the proposed entities is not an entity like, for example,
	"in the kitchen", you should signal it by writing "error" in the "Suggest a 
	new type / signal not an entity" field.</p>
	<p>Thank you for your time.</p>
</div>

<div class="article" style="width: 500px">
<h3>Text:</h3>
<?php 
	printArticle($task);
?>
</div>	

<form action="http://www.mturk.com/mturk/externalSubmit" method="post" onsubmit="return checkform(this);">
<input type="hidden" name="docID" value = "<?php echo $task['docID']; ?>">

<?php	
	$entities = $task["entities"];
	foreach( $entities as $entity ) {
		$types = $entity["types"];
		createTypesYesNoTable($entity, $types);
	}//foreach
	
}//if
?>
	<!-- This field gets populated by JavaScript when the page loads: -->
	<input type="hidden" name="assignmentId" id="myAssignmentId"
		value="<?php echo($assignmentId); ?>" /> 
	<br />
	Leave a comment on the task (if you want): <br />
	<textarea cols="80" rows="5" name="comments" id="commentsarea"></textarea><br />
	<input type="submit" value="submit" id="submit" />
	<input type="reset" value="reset"> 
</form>
</body>
</html>