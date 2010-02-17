<style type="text/css">
pre {
	overflow: auto;
	background: #FAFDFF;
	padding: 1em;
	margin: 1em 0;
	border: 1px solid #bedbeb;
}
</style>

<div style="padding: 0 3em; width: auto; background: #fff;" id="debug_dump">

<b>Page Requested</b>
<pre><?php print routes::fetch(); ?></pre>

<b>Actual Route Followed</b>
<pre><?php print implode('/', routes::fetch(TRUE)); ?></pre>

<b>Memory Usage</b>
<pre>
<?php print number_format(memory_get_peak_usage()); ?> bytes (peak)
<?php print number_format(memory_get_usage() - START_MEMORY_USAGE); ?> bytes (current)
</pre>


<b>Execution Time</b>
<pre><?php print round((microtime(true) - START_TIME), 5); ?> seconds</pre>

<?php
// Show queries to all database servers
if(class_exists('Database', FALSE) AND Database::$instances)
{
	foreach(Database::$instances as $name => $db)
	{
		print '<b>'. count($db->queries). ' Database Queries ("'. $name. '")</b>';
		$db->print_queries();
	}
}
?>

<?php
$included_files = get_included_files();
foreach($included_files as $id => $file) {
	//Remove site path
	$included_files[$id] = substr($file, strlen(SYSTEM_PATH));
}
?>
<b><?php print count($included_files); ?> PHP Files Included:</b>
<pre>
<?php print implode("\n", $included_files); ?>
</ul>
</pre>

<b>Classes Defined:</b>
<pre>
<?php
$classes = get_declared_classes();

//First class loaded by our system
$key = array_search('load', $classes);

//Remove PHP classes from the list an only show our loaded classes
if( $key !== NULL)
{
	$classes = array_slice($classes, $key);
}

foreach($classes as $class)
{
	print $class. "\n";
}
?>
</pre>

<b>Singleton Objects Created:</b>
<pre>
<?php print implode("\n", array_keys(load::$objects)); ?>
</pre>

<?php
foreach (array('_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER') as $var)
{
	if (empty($GLOBALS[$var]))
	{
		continue;
	}

	//Dump out the values
	print '<b>$'. $var. '</b>';
	print dump($GLOBALS[$var]);
}

/* Extra info as needed:


<b>Constants Defined:</b>
<pre>
<?php
$constants = get_defined_constants(true);
foreach($constants['user'] as $name => $value) {
	print $name. ' = '. $value. "\n";
}
?>
</pre>

<b>Config Settings</b>
<pre>
<?php
foreach( config::$config as $group => $config ) {
	print '<b>'. $group. '</b>'. "\n";
	print dump($config);
}
?>
</pre>

<b>Language Settings</b>
<pre>
<?php
foreach( lang::$lang as $group => $lang ) {
	print '<b>'. $group. '</b>'. "\n";
	print dump($lang);
}
?>
</pre>



// Insane debug info!
//$vars = get_defined_vars(); unset($vars['constants']); print dump($vars);
//phpinfo(); 

*/
?>

</div>