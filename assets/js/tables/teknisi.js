"use strict";

// ── Shared color palette ────────────────────────────────────────────────────
const COLORS = {
    install: '#10B981',
    service: '#3B82F6',
    dismantle: '#EF4444',
    primary: '#3B82F6',
    warning: '#F59E0B',
    muted: '#94A3B8',
};

// ── Chart 2: Monthly area chart ─────────────────────────────────────────────
var KTChartMonthly = function () {
    var _init = function () {
        const year = new Date().getFullYear();
        fetch(`${HOST_URL}api/get_chart.month.php?tahun=${year}`)
            .then(r => { if (!r.ok) throw new Error('fetch error'); return r.json(); })
            .then(res => {
                const options = {
                    series: [
                        { name: 'Instalasi', data: res.ikr },
                        { name: 'Service', data: res.service },
                        { name: 'Dismantle', data: res.dismantle },
                    ],
                    chart: {
                        type: 'area',
                        height: 240,
                        toolbar: { show: false },
                        sparkline: { enabled: false },
                        fontFamily: 'inherit',
                    },
                    colors: [COLORS.install, COLORS.service, COLORS.dismantle],
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                        dashArray: [0, 5, 2],
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            opacityFrom: 0.18,
                            opacityTo: 0.02,
                            stops: [0, 100],
                        },
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: res.labels,
                        labels: { style: { colors: COLORS.muted, fontSize: '11px' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: { style: { colors: COLORS.muted, fontSize: '11px' } },
                    },
                    grid: {
                        borderColor: '#F1F5F9',
                        strokeDashArray: 4,
                        padding: { left: 0, right: 0 },
                    },
                    legend: { show: false },
                    tooltip: {
                        theme: 'light',
                        shared: true,
                        intersect: false,
                    },
                    markers: {
                        size: 0,
                        hover: { size: 4 },
                    },
                };
                new ApexCharts(document.querySelector('#chart_2'), options).render();
            })
            .catch(err => console.warn('Monthly chart fetch failed:', err));
    };
    return { init: _init };
}();

// ── Chart 3: Monthly pie/donut ──────────────────────────────────────────────
var KTChartPie = function () {
    var _init = function () {
        fetch(`${HOST_URL}api/get_chart_pie.month.php`)
            .then(r => r.json())
            .then(res => {
                const options = {
                    series: res.series,
                    chart: {
                        type: 'donut',
                        height: 260,
                        fontFamily: 'inherit',
                    },
                    labels: res.labels,
                    colors: [COLORS.install, COLORS.service, COLORS.dismantle],
                    dataLabels: {
                        enabled: true,
                        formatter: (val) => Math.round(val) + '%',
                        style: { fontSize: '11px', fontWeight: 600 },
                        dropShadow: { enabled: false },
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '58%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '12px',
                                        color: COLORS.muted,
                                        formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                                    },
                                },
                            },
                        },
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center',
                        fontSize: '12px',
                        labels: { colors: '#475569' },
                        markers: { width: 10, height: 10, radius: 2 },
                        itemMargin: { horizontal: 8 },
                    },
                    stroke: { width: 0 },
                    tooltip: { theme: 'light' },
                    responsive: [{ breakpoint: 480, options: { chart: { height: 220 } } }],
                };
                new ApexCharts(document.querySelector('#chart_3'), options).render();
            })
            .catch(err => console.warn('Pie chart fetch failed:', err));
    };
    return { init: _init };
}();

// ── KTDatatable: Technician progress ───────────────────────────────────────
var KTDatatableTechProgress = function () {
    var _init = function () {
        var datatable = $('#kt_datatable').KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/teknisi_progres.php',
                    },
                },
                pageSize: 10,
                serverPaging: false,
                serverFiltering: true,
                serverSorting: false,
                saveState: { cookie: false, webstorage: false },
            },
            layout: {
                scroll: false,
                footer: false,
            },
            sortable: true,
            pagination: true,
            search: {
                input: $('#kt_datatable_search_query'),
                key: 'generalSearch',
            },
            columns: [
                {
                    field: 'tech_id',
                    title: 'ID Teknisi',

                    overflow: 'visible',
                    template: row => `<span style="font-size:0.75rem;color:#94a3b8;font-weight:600;white-space:nowrap">${row.tech_id}</span>`,
                },
                {
                    field: 'technician_name',
                    title: 'Nama Teknisi',
                    autoHide: false,

                    template: row => `
                        <div>
                            <a href="${HOST_URL}pages/teknisi/detail.php?id=${row.tech_id}"
                               style="font-size:0.82rem;font-weight:600;color:#1e293b;text-decoration:none"
                               onmouseover="this.style.color='#3B82F6'"
                               onmouseout="this.style.color='#1e293b'">
                                ${row.technician_name}
                            </a>
                        </div>
                    `,
                },
                {
                    field: 'progress',
                    title: 'Progress',
                    autoHide: false,
                    // width: 300,
                    template: function (row) {
                        const total = row.total_tugas;
                        const ins = row.total_instalasi;
                        const svc = row.total_service;
                        const dis = row.total_dismantle;
                        const done = row.total_done;

                        // Bar = completion percentage (done vs total), NOT task distribution
                        const pDone = total > 0 ? Math.round(done / total * 100) : 0;

                        // Pick bar color based on completion level
                        const barColor = pDone >= 100 ? '#10B981'   // selesai semua → hijau
                            : pDone >= 50 ? '#3B82F6'   // setengah → biru
                                : pDone > 0 ? '#F59E0B'   // sebagian → amber
                                    : '#e2e8f0';  // belum ada → abu

                        return `
                        <div style="min-width:220px">
                            <div style="display:flex;gap:10px;margin-bottom:5px;flex-wrap:wrap">
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;color:#64748b">
                                    <span style="width:6px;height:6px;border-radius:50%;background:#10B981;display:inline-block"></span>
                                    ${ins} ins
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;color:#64748b">
                                    <span style="width:6px;height:6px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
                                    ${svc} svc
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;color:#64748b">
                                    <span style="width:6px;height:6px;border-radius:50%;background:#EF4444;display:inline-block"></span>
                                    ${dis} dis
                                </span>
                            </div>
                            <div style="height:8px;border-radius:4px;background:#f1f5f9;overflow:hidden;position:relative">
                                <div style="width:${pDone}%;background:${barColor};height:100%;border-radius:4px;transition:width .5s ease"></div>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-top:4px;font-size:0.68rem;color:#94a3b8">
                                <span>${pDone}% selesai</span>
                                <span>${done}/${total}</span>
                            </div>
                        </div>
                        `;
                    },
                },
                {
                    field: 'total_done',
                    title: 'Selesai',
                    textAlign: 'center',
                    autoHide: false,


                    template: row => `
                        <span style="font-size:0.85rem;font-weight:700;color:#1e293b">${row.total_done}</span>
                        <span style="font-size:0.72rem;color:#94a3b8">/ ${row.total_tugas}</span>
                    `,
                },
            ],
        });

        // Filters
        $('#kt_datatable_search_tech').on('change', function () {
            datatable.search($(this).val().toLowerCase(), 'tech_id');
        });
        $('#kt_datatable_search_query').on('keyup', function () {
            datatable.search($(this).val(), 'generalSearch');
        });
    };

    return { init: _init };
}();

// ── Bootstrap ───────────────────────────────────────────────────────────────
jQuery(document).ready(function () {
    KTChartMonthly.init();
    KTChartPie.init();
    KTDatatableTechProgress.init();
});