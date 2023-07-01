<?php

function kirim_person($key, $no_hp, $pesan)
{
    $curl2 = curl_init();
    curl_setopt_array(
        $curl2,
        array(
            CURLOPT_URL => 'http://191.101.3.115:3000/api/sendMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'apiKey=' . $key . '&phone=' . $no_hp . '&message=' . $pesan,
        )
    );
    $response = curl_exec($curl2);
    curl_close($curl2);
}

function kirim_group($key, $id_group, $pesan)
{
    $curl2 = curl_init();
    curl_setopt_array(
        $curl2,
        array(
            CURLOPT_URL => 'http://191.101.3.115:3000/api/sendMessageGroup',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'apiKey=' . $key . '&id_group=' . $id_group . '&message=' . $pesan,
        )
    );
    $response = curl_exec($curl2);
    curl_close($curl2);
}
