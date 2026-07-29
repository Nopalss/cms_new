<?php

require_once __DIR__ . "/../../includes/config.php";
include __DIR__ . "/../token/env.php";

function sendCustomerUpdateToMinpo($pdo, $netpayId)
{
    global $env;

    $stmt = $pdo->prepare("
        SELECT
            c.netpay_id,
            c.name,
            c.phone,
            c.is_active,

            d.email,
            d.package_external_id,
            d.package_name,
            d.monthly_bill,

            d.inst_street,
            d.inst_rt,
            d.inst_rw,
            d.inst_village,
            d.inst_district,
            d.inst_city,
            d.inst_province,
            d.inst_zip

        FROM customers c
        LEFT JOIN customer_details d
            ON d.netpay_id = c.netpay_id

        WHERE c.netpay_id = :id
        LIMIT 1
    ");

    $stmt->execute(array(
        ":id" => $netpayId
    ));

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {

        return array(
            "status" => false,
            "message" => "Customer tidak ditemukan"
        );
    }

    $payload = array(

        "customer_id" => $customer["netpay_id"],
        "full_name"   => $customer["name"],
        "phone"       => $customer["phone"],
        "email"       => $customer["email"],

        "package_external_id" => $customer["package_external_id"],
        "package_name"        => $customer["package_name"],
        "monthly_bill"        => (int)$customer["monthly_bill"],

        "inst_street"   => $customer["inst_street"],
        "inst_rt"       => $customer["inst_rt"],
        "inst_rw"       => $customer["inst_rw"],
        "inst_village"  => $customer["inst_village"],
        "inst_district" => $customer["inst_district"],
        "inst_city"     => $customer["inst_city"],
        "inst_province" => $customer["inst_province"],
        "inst_zip"      => $customer["inst_zip"],

        "status" => strtoupper($customer["is_active"]) == "ACTIVE"
            ? "active"
            : "inactive"

    );

    $url = rtrim($env["JBR_MINPO_API_URL"], "/")
        . "/api/v1/webhook/customer-update-from-office";

    $options = array(
        "http" => array(
            "method" => "POST",
            "header" =>
            "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $env["JBR_MINPO_INTEGRATION_TOKEN"] . "\r\n",
            "content" => json_encode($payload),
            "ignore_errors" => true,
            "timeout" => 30
        )
    );

    $context = stream_context_create($options);

    $response = @file_get_contents($url, false, $context);

    $statusCode = 0;

    if (isset($http_response_header[0])) {

        preg_match('/\d{3}/', $http_response_header[0], $match);

        if (isset($match[0])) {
            $statusCode = (int)$match[0];
        }
    }

    return array(
        "status" => ($statusCode >= 200 && $statusCode < 300),
        "status_code" => $statusCode,
        "response" => json_decode($response, true),
        "raw_response" => $response
    );
}
