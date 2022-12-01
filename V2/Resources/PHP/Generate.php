<?php
require_once("SetFilePath.php");
require_once("CommonFunctions.php");
require_once("SetDBConnection.php");

// File containing System Variables
require LOCAL_PATH_ROOT . '/uas_tools/system_management/centralized_management.php';

$pageID = $_GET["pageid"];
$groups = $_REQUEST["groups"];
$projectID = $_GET["project"];
$pageTitle = $_GET["name"];
$center = $_GET["center"];
$zoom = $_GET["zoom"];
$minZoom = $_GET["minZoom"];
$maxZoom = $_GET["maxZoom"];

//print_r($_GET);

$con = SetDBConnection();


if (mysqli_connect_errno()) {
    echo "Failed to connect to database server: " . mysqli_connect_error();
} else {
    $deleteSQL = "delete from visualization_project where id = $pageID";
    mysqli_query($con, $deleteSQL);


// THIS IS USED TO GET THE VALUES FROM THE $_GET VARIABLE
//Type = 1 MEANS RGB FILES
//Type = 8 MEANS GeoJSON FILES
// THIS PAGE IS ONLY LOADED ONCE AFTER CLICKIN ON THE 'Generate' button
// foreach ($_GET['groups'] as $group){
//     // var_dump($group['Type']);
//     $type = $group['Type'];
// //}
// _log("type: " .$type);


// LOGIC FOR TABLE imagery_product CONTAINING RGB FILES
//if ($type != "%" && $type != "8") {
    // $sql = "select imagery_product.*, flight.Date as Date, product_type.Type as producttype " .
    //     "from imagery_product, flight, product_type " .
    //     "where flight.Project = $projectID and imagery_product.Flight = flight.ID " .
    //     "and imagery_product.Type = product_type.ID and imagery_product.Status = 'Finished' " .
    //     "order by Filename";
      //}

    // Need to create or modify new SQL that queries vector_data table
//elseif ($type == "8"){


// LOGIC FOR TABLE vector_data CONTAINING GeoJSON FILES
$type = 8;

    $typeCondition =  " and v.Type = $type ";

    $sql = "SELECT v.*, project.Name as ProjectName, t.Name as TypeName, product_type.Type as producttype " .
       "from project, product_type, vector_data v inner join product_type t on v.Type = t.ID " .
       "where product_type.Type = 'V' and v.Status = 'Finished' " .
       "and v.Project =  project.Name " .
       //"order by ProjectName, TypeName";
    $typeCondition.
    //"order by Filename";
    "";

    _log("SQL: " .$sql);
  //}

    ////////

    $folderPath = SetTempFolderLocalPath() . FormatFileName($pageTitle);
    if (!file_exists($folderPath)) {
        if (!mkdir($folderPath, 0777, true)) {
            die('Failed to create folders...');
        }
    }

    $pagepath = $folderPath . "/index.html";

    // Added
    $folderPathMobile = SetTempFolderLocalPathMobile() . FormatFileName($pageTitle);
    if (!file_exists($folderPathMobile)) {
        if (!mkdir($folderPathMobile, 0777, true)) {
            die('Failed to create folders...');
        }
    }

    $pagepathmobile = $folderPathMobile . "/index.html";

    // If session hasn't been started, start it
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    //$header_location = $_SESSION['header_location'];
    //$root_path = $_SESSION['root_path'];
    $viewPath = str_replace($root_path, $header_location . "/", $pagepath);

    // Added
    $viewPathMobile = str_replace($root_path, $header_location . "/", $pagepathmobile);
    //

    $addSQL = "insert into visualization_project (Name, Project, Path, PathMobile) " .
        "values ('$pageTitle', '$projectID', '$viewPath', '$viewPathMobile')";
    mysqli_query($con, $addSQL);
    $vProjectID = $con->insert_id;

    $result = mysqli_query($con, $sql);
    $productList = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $productList[] = $row;
    }

    $zIndex = 50;
    //print_r($productList);

    $layerText = "";
    $overLayerText = "";

    $firstLayer = true;

    foreach ($groups as $group) {
        //print_r($group);
        //$idList = explode(";",$group["IDs"]);
        $idList = preg_split("@;@", $group["IDs"], NULL, PREG_SPLIT_NO_EMPTY);
        if (count($idList) > 0) {

            $addSQL = "insert into visualization_group (Name, Type, Project) " .
                "values ('" . $group["GroupName"] . "', '" . $group["Type"] . "',$vProjectID)";
            mysqli_query($con, $addSQL);
            $vGroupID = $con->insert_id;

            $groupText = "{\n" .
                "\tgroup: '" . $group["GroupName"] . "',\n" .
                "\tlayers: [\n";

            foreach ($idList as $id) {
                foreach ($productList as $product) {
                    if ($product["ID"] == $id) {

                        $addSQL = "insert into visualization_layer (Layer, GroupID) " .
                            "values ($id, $vGroupID)";
                        mysqli_query($con, $addSQL);

                        $boundaryText = "";

                        //print_r($product); // need to add these fields to vector data: Boundary producttype TMSPath

                        if ($product["Boundary"] != "") {
                            $bounds = explode(";", $product["Boundary"]);
                            $boundaryText = ", bounds: L.latLngBounds([";
                            foreach ($bounds as $bound) {
                                $point = "L.latLng(" . $bound . "),";
                                $boundaryText .= $point;
                            }

                            $boundaryText = rtrim($boundaryText, ",") . "])";
                        }
                        //$boundaryText = "";
                        //echo "boundary text:'".$boundaryText."'";

                        //_log("Product type: " .$product["producttype"]);

                        // If product is Vector File
                        if ($product["producttype"] == "V") {
                            //_log('test: ' . $product["UploadFolder"]);

                            $corrected_path = str_ireplace("/var/www/html", "", $product["UploadFolder"]);

                            //_log('$corrected_path: ' . $corrected_path);

                            $base_json_name = str_ireplace(".geojson", "", $product["FileName"]);
                            $layerName = "layer_" . $base_json_name;
                            $paneName = "pane_" . $base_json_name;

                            $layer1 = "map.createPane('" . $paneName . "'); \n";
                            $layer2 = "map.getPane('" . $paneName . "').style.zIndex = " . $zIndex . "; \n";
                            $layer3 = "map.getPane('" . $paneName . "').style.pointerEvents = 'none'; \n";
                            //$layer4 = "var " . $layerName . " = new L.GeoJSON.AJAX('" . $product["UploadFolder"] . "/" . $product["FileName"] . "', {pane: '" . $paneName . "'}); \n";
                            //$layer4 = "var " . $layerName . " = new L.GeoJSON.AJAX('" . $corrected_path . "/" . $product["FileName"] . "', {pane: '" . $paneName . "'}); \n";

                            // All working GeoJSON files will include _converted on their name.
                            // Rename and use this name instead
                            $newName = str_replace(".geojson","_converted.geojson",$product["FileName"]);

                            // Removed pane to make geoJSON work
                            //$layer4 = "var " . $layerName . " = new L.GeoJSON.AJAX('" . $corrected_path . "/" . $product["FileName"] . "'); \n";
                            $layer4 = "var " . $layerName . " = new L.GeoJSON.AJAX('" . $corrected_path . "/" . $newName . "'); \n";

                            //_log("layer4: " .$layer4);

                            //_log("newName: " .$newName);

                            $layer = $layer1 . $layer2 . $layer3 . $layer4;
                            //_log("Layer: " .$layer);

                        } else {

                            $layerName = "layer_" . str_ireplace(".tif", "", $product["FileName"]);
                            $layer = "var " . $layerName . " = L.tileLayer('" . $product["TMSPath"] .
                                "', {tms: true, zIndex: " . $zIndex . $boundaryText . "}); \n";

                        }

                        $layerText .= $layer;

                        if ($firstLayer) {
                            $activeText = "active: 'true',\n";
                            $firstLayer = false;
                        } else {
                            $activeText = "";
                        }

                        $groupText .= "\t\t{\n" .
                            "\t\t\tname: '" . str_replace("-", "/", $product["Date"]) . "',\n" .
                            $activeText .
                            "\t\t\tlayer: " . $layerName . "\n" .
                            "\t\t},\n";

                        $zIndex++;
                    }
                }
            }

            $groupText .= "\t]\n" .
                "},\n";

            $overLayerText .= $groupText;
        }
    }

    $templatePath = getcwd() . "/page_template.html";
    //_log('$templatePath: ' . $templatePath);
    $pageContent = file_get_contents($templatePath);
    //_log('$pageContent: ' . $pageContent);
    //_log('layerText: ' . $layerText);
    //_log('overLayerText: ' .$overLayerText);

    //file_put_contents($templatePath, $pageContent);
    $pageContent = str_replace("#PAGE-TITLE#", $pageTitle, $pageContent);
    $pageContent = str_replace("#PROJECT-CENTER#", $center, $pageContent); // check this value
    $pageContent = str_replace("#DEFAULT-ZOOM#", $zoom, $pageContent);
    $pageContent = str_replace("#MIN-ZOOM#", $minZoom, $pageContent);
    $pageContent = str_replace("#MAX-ZOOM#", $maxZoom, $pageContent);
    $pageContent = str_replace("#LAYERS#", $layerText, $pageContent);
    $pageContent = str_replace("#OVER-LAYERS#", $overLayerText, $pageContent);

    echo $pageContent;

    $file = fopen($pagepath, "w") or die("Unable to open file!");
    fwrite($file, $pageContent);
    fclose($file);

    // Added
    //$templatePath = getcwd() . "/page_template_mobile.html";
    $templatePath = getcwd() . "/page_template_mobile.php";
    echo $templatePath;
    $pageContent = file_get_contents($templatePath);
//  echo $pageContent;

    //file_put_contents($templatePath, $pageContent);
    $pageContent = str_replace("#PAGE-TITLE#", $pageTitle, $pageContent);
    $pageContent = str_replace("#PROJECT-CENTER#", $center, $pageContent); // check this value
    //$pageContent = str_replace("#PROJECT-CENTER#", $first, $pageContent); // check this value
    $pageContent = str_replace("#DEFAULT-ZOOM#", $zoom, $pageContent);
    $pageContent = str_replace("#MIN-ZOOM#", $minZoom, $pageContent);
    $pageContent = str_replace("#MAX-ZOOM#", $maxZoom, $pageContent);
    $pageContent = str_replace("#LAYERS#", $layerText, $pageContent);
    $pageContent = str_replace("#OVER-LAYERS#", $overLayerText, $pageContent);

    echo $pageContent;

    //_log('$pagepathmobile: '.$pagepathmobile);

    $file = fopen($pagepathmobile, "w") or die("Unable to open file!");
    fwrite($file, $pageContent);
    fclose($file);
    //

    echo $viewPath;
}
?>
