<?php
    try {
        $pdoConnect = new PDO('mysql:host=localhost;dbname=test_db','root', '');
    } catch (PDOException $exc) {
        echo $exc->getMessage();
        die();
    }

    $country = $_POST['country'];
    $postCode = $_POST['postal_code'];

    $result = $pdoConnect->prepare(
        "SELECT zcd.place_name, zcd.longitude, zcd.latitude FROM postal_code AS pc 
                  LEFT JOIN zip_code_data as zcd ON pc.id = zcd.zip_code_id 
                  WHERE postal_code=:postcode  AND country_code=:countryCode",
        [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
    );
    $result->execute([':postcode'=>$postCode, ':countryCode' => strtoupper($country)]);
    $data = $result->fetchAll();

    if ($data) {
        echo json_encode($data);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            "http://api.zippopotam.us/" . $_POST['country'] . "/" . $_POST['postal_code']
        );

        $content = curl_exec($ch);
        curl_close($ch);

        $apiData = json_decode($content, true);

        if (!empty($apiData)) {
            $insertQuery = $pdoConnect->prepare(
                "INSERT INTO postal_code (country, country_code, postal_code)VALUES (:country, :countryCode, :postcode);",
                [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
            );
            $insertQuery->execute([':country' => $apiData['country'],':postcode' => $apiData['post code'], ':countryCode' => $apiData['country abbreviation']]);
            $zipId = $pdoConnect->lastInsertId();

            foreach ($apiData['places'] as $row) {
                $zipData = $pdoConnect->prepare(
                    "INSERT INTO zip_code_data (zip_code_id, place_name, longitude, latitude)VALUES (:zip_code_id, :place_name, :longitude, :latitude);",
                    [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
                );
                $zipData->execute([':zip_code_id' => $zipId,':place_name' => $row['place name'], ':longitude' => $row['longitude'], ':latitude' => $row['latitude']]);
            }

            $selectZipData = $pdoConnect->prepare("SELECT place_name, longitude, latitude FROM zip_code_data WHERE zip_code_id =:zip_code_id");
            $selectZipData->execute([':zip_code_id'=>$zipId]);

            echo json_encode($selectZipData->fetchAll());

        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nothing found by your zip code'
            ]);
        }
    }
?>