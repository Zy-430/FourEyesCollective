<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Product Sales Report';
?>

<div class="admin-content view-product-report">
    <div class="content-header">
        <h1 class="dashboard-title">Top Selling Products Report</h1>
        <div style="margin-bottom:20px;">
            <button onclick="window.print()"
                style="padding:10px 18px; background:#2c3e50; color:white;
               border:none; border-radius:5px; cursor:pointer; margin-top:20px;">
                Print / Download PDF
            </button>
        </div>
    </div>


    <!-- CHART CONTAINER -->
    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #ddd;">
        <canvas id="salesChart"></canvas>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
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