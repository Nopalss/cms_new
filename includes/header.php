<?php
require_once __DIR__ . '/config.php';
require __DIR__ . '/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <base href="">
    <meta charset="utf-8" />
    <title>CMS JABBAR23 </title>
    <meta name="description" content="Updates and statistics" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!--begin::Fonts-->
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" /> end::Fonts -->

    <!--begin::Page Vendors Styles(used by this page)-->
    <!-- <link href="<?= BASE_URL ?>assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" /> -->
    <!--end::Page Vendors Styles-->


    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="<?= asset_ver('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= asset_ver('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles-->

    <!--begin::Layout Themes(used by all pages)-->

    <link href="<?= asset_ver('assets/css/themes/layout/header/base/light.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= asset_ver('assets/css/themes/layout/brand/dark.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= asset_ver('assets/css/themes/layout/aside/dark.css') ?>" rel="stylesheet" type="text/css" />

    <link rel="shortcut icon" href="<?= asset_ver('assets/media/favicon.ico') ?>" />

    <!-- Progressive Web App (PWA) Meta & Manifest -->
    <link rel="manifest" href="<?= asset_ver('manifest.json') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JTracks">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/media/logos/icon-192.png">
    <meta name="theme-color" content="#0E7C7B">
</head>