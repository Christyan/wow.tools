<?
require_once("../inc/header.php");
$bc1 = getBuildConfigByBuildConfigHash($_GET['from']);
$bc2 = getBuildConfigByBuildConfigHash($_GET['to']);
?>
<div class='container-fluid' id='diffContainer'>
<pre style='color: var(--text-color)'>
<?
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:5005/casc/root/diff?from=" . $bc1['root_cdn']. "&to=" . $bc2['root_cdn']) . "&cb= " . strtotime("now");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$data = curl_exec($ch);
curl_close($ch);
print_r($data);
if($data == ""){
	echo "No differences found.";
}
?>
</pre>
</div>
<?
require_once("../inc/footer.php");
?>