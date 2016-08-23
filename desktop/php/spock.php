<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'spock');
$eqLogics = eqLogic::byType('spock');

?>

<script>

var results =  {};
var runningProcesses = 0;
var runningProcessesDesc = [];

function openMarket(plugin_id) {
  $('#md_modal').dialog({title: "{{Market module plugin}}"});
  $('#md_modal').load('index.php?v=d&modal=market.display&type=plugin&id=' + plugin_id).dialog('open');
}

function updateProcessDisplay()
{
    $("#running").html(runningProcesses);

    if (runningProcesses==0) {
      $("#refreshButton").removeAttr('disabled');
    } else {
      $("#refreshButton").attr('disabled','disabled');
    }

    $("#runningprocess").html(runningProcessesDesc.join());
}

function loadEngine(engine) {
  runningProcesses++;
  runningProcessesDesc.push(engine);

  updateProcessDisplay();


  $.ajax({
    url: "plugins/spock/core/ajax/spock.ajax.php?action=execEngine&name=" + engine,
    cache: false,
    dataType: "json",
    success: function( data ) {
      runningProcesses--;
      var index = runningProcessesDesc.indexOf(engine);
      if (index > -1) {
          runningProcessesDesc.splice(index, 1);
      }

      updateProcessDisplay();

      if (data !== null) {

        for (index = 0; index < data.length; ++index) {
          var item = data[index];

          var id = item.logicalId + "-" + item.ip;

          if(results[id] === undefined) {
            $("#table_spock").append(item.html);
            results[id]="1";
          }
        }
      }
    }
  });
  $.hideLoading();
}

function searchEngine() {
  <?php
if ($handle = opendir(dirname(__FILE__) . '/../../3rdparty/engine')) {

	while (false !== ($entry = readdir($handle))) {
		if (strstr($entry, ".php") != "") {
			printf("loadEngine(\"" . str_replace(".php", "", $entry) . "\");\n");
		}
	}

	closedir($handle);
}

?>

}

$( document ).ready(function() {
  searchEngine();

  $('.searchEngine').on('click', function () {
    searchEngine();
  });

});




</script>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{Mes templates}} </legend>

    <a class="btn btn-default btn-sm tooltips searchEngine" style="width : 100%" id="refreshButton"><i class="fa fa-refresh"></i> {{Refresh}}</a>

<table id="table_spock" class="table table-bordered table-condensed tablesorter">
    <thead>
        <tr>
            <th>IP</th>
            <th>Img</th>
            <th>Plugin</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
  </table>

<span id="running"></span> process en cours. <span id="runningprocess"></span>

</div>

<?php include_file('desktop', 'spock', 'js', 'spock');?>
<?php include_file('core', 'plugin.template', 'js');?>
