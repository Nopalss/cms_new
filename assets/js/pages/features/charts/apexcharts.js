"use strict";

// Shared Colors Definition
const primary = '#6993FF';
const success = '#1BC5BD';
const info = '#8950FC';
const warning = '#FFA800';
const danger = '#F64E60';

// Class definition
function generateBubbleData(baseval, count, yrange) {
	var i = 0;
	var series = [];
	while (i < count) {
		var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
		var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
		var z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

		series.push([x, y, z]);
		baseval += 86400000;
		i++;
	}
	return series;
}

function generateData(count, yrange) {
	var i = 0;
	var series = [];
	while (i < count) {
		var x = 'w' + (i + 1).toString();
		var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;

		series.push({
			x: x,
			y: y
		});
		i++;
	}
	return series;
}

var KTApexChartsDemo = function () {
	// Private functions


	var _demo2 = function () {
		fetch(`${HOST_URL}api/get_chart.month.php?tahun=2024`)
			.then(response => {
				if (!response.ok) throw new Error('Network response was not ok');
				return response.json();
			})
			.then(res => {
				const options = {
					series: [
						{ name: 'IKR', data: res.ikr },
						{ name: 'Service', data: res.service },
						{ name: 'Dismantle', data: res.dismantle }
					],
					chart: {
						height: 350,
						type: 'area'
					},
					xaxis: {
						categories: res.labels
					},
					colors: ['#28a745', '#007bff', '#ff0505ff'],
					stroke: {
						curve: 'smooth'
					},
					dataLabels: {
						enabled: false
					}
				};

				new ApexCharts(document.querySelector("#chart_2"), options).render();
			})
			.catch(error => {
				console.error('Fetch error:', error);
			});
	}

	var _demo3 = function () {
		fetch(`${HOST_URL}api/get_chart_pie.month.php`)
			.then(r => r.json())
			.then(res => {
				const apexChart = "#chart_3";
				const options = {
					series: res.series,
					chart: {
						width: '100%',
						type: 'pie',
						height: 314
					},
					labels: res.labels,
					legend: {
						position: 'bottom',     // ⬅️ pindahkan ke bawah chart
						horizontalAlign: 'center', // biar rata tengah
						markers: {
							width: 12,
							height: 12
						}
					},
					responsive: [{
						breakpoint: 480,
						options: {
							chart: { width: 200 },
							legend: { position: 'bottom' }
						}
					}],
					colors: ['#28a745', '#007bff', '#ff0505'] // green, blue, red
				};

				new ApexCharts(document.querySelector(apexChart), options).render();
			});
	}

	return {
		// public functions
		init: function () {
			_demo2();
			_demo3();
		}
	};
}();

jQuery(document).ready(function () {
	KTApexChartsDemo.init();
});
