<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Product Sales Report | Four Eyes Collective';
?>

<div class="admin-content view-product-report">
    <div class="content-header">
        <h1 class="dashboard-title">Top Selling Products Report</h1>
        <div class="header-actions small">
            <button onclick="window.print()"
                class="btn-default btn-add">
                <i class="fa-solid fa-print"></i>  Print
            </button>

            <button onclick="downloadChart()"
                class="btn-default btn-add">
                <i class="fa-solid fa-download"></i> Download
            </button>
        </div>
    </div>

    <!-- CHART CONTAINER -->
    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #ddd;">
        <canvas id="salesChart" height="550"></canvas>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function downloadChart() {
            const canvas = document.getElementById('salesChart');

            // Create an invisible link
            const link = document.createElement('a');
            link.download = "top_sales_chart.png";
            link.href = canvas.toDataURL("image/png");

            // Trigger the download
            link.click();
        }

        $(function() {

            $.ajax({
                url: '../ajax/product_report_data.php',
                dataType: 'json',
                success: function(data) {

                    if (data.length === 0) {
                        alert('No sales data available.');
                        return;
                    }

                    const labels = [];
                    const values = [];

                    $.each(data, function(i, row) {
                        labels.push(row.product_name);
                        values.push(row.total_sold);
                    });

                    const ctx = document.getElementById('salesChart');

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Quantity Sold',
                                data: values,
                                backgroundColor: '#3498db'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Top 5 Best Selling Products'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                },
                error: function() {
                    alert('Failed to load report data.');
                }
            });

        });
    </script>

    <style>
        /* PRINT STYLING */
        @media print {
            button {
                display: none;
            }
        }
    </style>
