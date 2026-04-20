<?php
session_start();
require_once('connect.php');

// 檢查用戶是否登入
if (empty($_SESSION['login'])) {
  header('Location: login.php?msg=please_login');
  exit;
}

// 獲取用戶資料
$member_id = $_SESSION['member_id'];
$member_name = $_SESSION['login'];

// 獲取產品資料
$products = [];
$product_res = safeQuery('SELECT * FROM products ORDER BY product_name ASC');
if ($product_res->success) {
  $products = $product_res->result->fetch_all(MYSQLI_ASSOC);
}

// 獲取購買記錄資料
$purchase_records = [];
$purchase_res = safeQuery('SELECT pr.*, p.product_name, m.member_name 
                          FROM purchase_records pr 
                          JOIN products p ON pr.product_id = p.product_id 
                          JOIN member m ON pr.Member_id = m.Member_id 
                          ORDER BY pr.created_at DESC');
if ($purchase_res->success) {
  $purchase_records = $purchase_res->result->fetch_all(MYSQLI_ASSOC);
}

// 計算熱門商品
$hot_products = [];
$hot_res = safeQuery('SELECT p.*, COALESCE(SUM(pr.qty), 0) as total_sales
                     FROM products p
                     LEFT JOIN purchase_records pr ON p.product_id = pr.product_id
                     GROUP BY p.product_id
                     ORDER BY total_sales DESC
                     LIMIT 10');
if ($hot_res->success) {
  $hot_products = $hot_res->result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理頁面 - 資料採礦分析</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .admin-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
      position: relative;
      height: 400px;
      margin: 20px 0;
    }

    .card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin: 10px 0;
      border-left: 4px solid #007bff;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }

    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .stat-number {
      font-size: 2em;
      font-weight: bold;
      color: #007bff;
    }

    .stat-label {
      color: #666;
      margin-top: 5px;
    }

    .product-list {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .product-list ul {
      list-style-type: none;
      padding: 0;
    }

    .product-list li {
      background-color: white;
      margin-bottom: 15px;
      padding: 15px;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .product-details {
      flex: 1;
    }

    .product-details strong {
      font-size: 1.2em;
      color: #007bff;
    }

    .product-info {
      margin-top: 10px;
      display: grid;
      grid-template-columns: 1fr 180px;
      gap: 12px 20px;
      align-items: start;
      line-height: 1.4;
    }

    .product-row {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: flex-start;
    }

    .meta-left {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .meta-right {
      text-align: right;
      min-width: 140px;
    }

    .product-meta {
      color: #555;
      font-size: 0.95em;
    }

    .meta-label {
      color: #666;
      margin-right: 6px;
      font-weight: 600;
    }

    .product-price {
      color: #e83e8c;
      font-weight: 700;
      font-size: 1.05em;
    }

    .product-desc {
      grid-column: 1 / -1;
      color: #333;
      margin-top: 8px;
    }

    @media (max-width: 700px) {
      .product-list li {
        flex-direction: column;
        align-items: stretch;
      }

      .product-info {
        grid-template-columns: 1fr;
      }

      .meta-right {
        text-align: left;
      }
    }
  </style>
</head>

<body>
  <?php require_once 'nav.php'; ?>
  <div class="content admin-container">
    <h1>管理頁面 - 資料採礦分析</h1>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <a href="index.php">返回主頁</a>
      <div>歡迎，<?php echo htmlspecialchars($member_name); ?></div>
      <a href="product_list.php">查看商品列表</a>
    </div>

    <!-- 統計卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?php echo count($products); ?></div>
        <div class="stat-label">總商品數</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo count($purchase_records); ?></div>
        <div class="stat-label">總交易筆數</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo array_sum(array_column($hot_products, 'total_sales')); ?></div>
        <div class="stat-label">總銷售量</div>
      </div>
    </div>

    <!-- 熱門商品圖表 -->
    <div class="card">
      <h2 style="text-align: center;">熱門商品銷售況</h2>
      <div class="chart-container">
        <canvas id="hotProductsChart"></canvas>
      </div>
    </div>

    <!-- 時間軸圖表 -->
    <div class="card">
      <h2 style="text-align: center;">時間軸圖表 - 產品購買趨勢</h2>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
          <label>時間範圍：</label>
          <select id="timeRangeSelect" class="filter-select">
            <option value="1d">一天內</option>
            <option value="1w">一週內</option>
            <option value="1m">一個月內</option>
            <option value="1y">一年內</option>
            <option value="5y">五年內</option>
            <option value="all">最長</option>
          </select>
        </div>
        <div>
          <label>篩選條件：</label>
          <select id="filterTypeSelect" class="filter-select">
            <option value="all">所有類型</option>
            <option value="drinks">飲料</option>
            <option value="food">食品</option>
            <option value="toy">玩具</option>
            <option value="e-things">電子產品</option>
          </select>
        </div>
        <div>
          <label>選擇產品：</label>
          <select id="productFilterSelect" class="filter-select" multiple>
            <option value="all">所有產品</option>
            <?php foreach ($products as $product): ?>
              <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="chart-container">
        <canvas id="timeSeriesChart"></canvas>
      </div>
      <div id="chartLegend" style="margin-top: 20px;"></div>
    </div>

    <!-- 熱門商品列表 -->
    <div class="card">
      <h2 style="text-align: center; margin-bottom: 0.5rem;">熱門商品 (Top 10)</h2>
      <div class="product-list">
        <ul>
          <?php foreach ($hot_products as $product): ?>
            <li>
              <div class="product-details">
                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                <div class="product-info">
                  <div class="product-row">
                    <div class="meta-left">
                      <div class="product-meta"><span class="meta-label">類型：</span><span><?php echo htmlspecialchars($product['type']); ?></span></div>
                      <div class="product-meta"><span class="meta-label">供應商：</span><span><?php echo htmlspecialchars($product['supplier']); ?></span></div>
                    </div>
                    <div class="meta-right">
                      <div class="product-price">$ <?php echo htmlspecialchars($product['price']); ?></div>
                      <div class="product-meta">銷售量：<?php echo htmlspecialchars($product['total_sales']); ?></div>
                    </div>
                  </div>
                  <div class="product-desc">描述： <?php echo htmlspecialchars($product['description']); ?></div>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- 互補商品推薦 -->
    <div class="card">
      <h2 style="text-align: center;">互補商品推薦</h2>
      <p>選擇一個商品查看互補商品推薦：</p>
      <select id="productSelect" class="filter-select">
        <option value="">選擇商品...</option>
        <?php foreach ($products as $product): ?>
          <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="recommendationResult" style="margin-top: 20px;"></div>
    </div>

    <!-- 建議清單 -->
    <div class="card">
      <h2 style="text-align: center;">建議清單</h2>
      <p>選擇一個會員查看建議清單：</p>
      <select id="memberSelect" class="filter-select">
        <option value="">選擇會員...</option>
        <?php
        $members = [];
        $member_res = safeQuery('SELECT * FROM member ORDER BY member_name ASC');
        if ($member_res->success) {
          $members = $member_res->result->fetch_all(MYSQLI_ASSOC);
        }
        foreach ($members as $member): ?>
          <option value="<?php echo $member['Member_id']; ?>"><?php echo htmlspecialchars($member['member_name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="suggestionResult" style="margin-top: 20px;"></div>
    </div>
  </div>

  <script>
    // 熱門商品圖表
    const ctx = document.getElementById('hotProductsChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($hot_products, 'product_name')); ?>,
        datasets: [{
          label: '銷售量',
          data: <?php echo json_encode(array_column($hot_products, 'total_sales')); ?>,
          backgroundColor: 'rgba(0, 123, 255, 0.6)',
          borderColor: 'rgba(0, 123, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        },
        responsive: true,
        maintainAspectRatio: false
      }
    });

    // 時間軸圖表
    let timeSeriesChart = null;

    function updateTimeSeriesChart() {
      const timeRange = document.getElementById('timeRangeSelect').value;
      const filterType = document.getElementById('filterTypeSelect').value;
      const productFilter = document.getElementById('productFilterSelect');
      const productIds = Array.from(productFilter.selectedOptions)
        .filter(option => option.value !== 'all')
        .map(option => option.value);

      fetch('get_time_series.php?time_range=' + timeRange +
          '&filter_type=' + filterType +
          '&product_ids=' + productIds.join(','))
        .then(response => {
          if (!response.ok) {
            throw new Error('網路請求失敗: ' + response.statusText);
          }
          return response.json();
        })
        .then(data => {
          if (data.success && data.time_series_data) {
            // 準備圖表資料
            const datasets = [];
            const colors = [
              'rgba(255, 99, 132, 0.5)',
              'rgba(54, 162, 235, 0.5)',
              'rgba(255, 206, 86, 0.5)',
              'rgba(75, 192, 192, 0.5)',
              'rgba(153, 102, 255, 0.5)',
              'rgba(255, 159, 64, 0.5)'
            ];

            data.time_series_data.forEach((productData, index) => {
              const color = colors[index % colors.length];
              const dataset = {
                label: productData.product_name,
                data: [],
                borderColor: color.replace('0.5', '1'),
                backgroundColor: color,
                fill: false,
                yAxisID: 'y-axis-1'
              };

              // 準備資料點
              const dates = Object.keys(productData.data).sort();
              dates.forEach(date => {
                const qty = productData.data[date].total_qty;
                dataset.data.push({
                  x: date,
                  y: qty
                });
              });

              datasets.push(dataset);
            });

            // 更新圖表
            if (timeSeriesChart) {
              timeSeriesChart.destroy();
            }

            const ctx = document.getElementById('timeSeriesChart').getContext('2d');
            timeSeriesChart = new Chart(ctx, {
              type: 'line',
              data: {
                datasets: datasets
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    type: 'time',
                    time: {
                      unit: 'day',
                      displayFormats: {
                        day: 'YYYY-MM-DD'
                      }
                    },
                    title: {
                      display: true,
                      text: '日期'
                    }
                  },
                  y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                      display: true,
                      text: '銷售量'
                    }
                  }
                },
                plugins: {
                  legend: {
                    display: true,
                    position: 'top'
                  },
                  tooltip: {
                    mode: 'index',
                    intersect: false
                  }
                }
              }
            });

            // 更新圖例
            let legendHtml = '<h3 style="margin-top: 1rem;">圖例：</h3><ul>';
            data.time_series_data.forEach((productData, index) => {
              const color = colors[index % colors.length];
              legendHtml += `<li style="color: ${color.replace('0.5', '1')}; margin-bottom: 0.5rem;">
                                      <strong>${productData.product_name}</strong> (${productData.type})
                                    </li>`;
            });
            legendHtml += '</ul>';
            document.getElementById('chartLegend').innerHTML = legendHtml;
          } else {
            document.getElementById('chartLegend').innerHTML = '<p>暫無資料可顯示</p>';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('chartLegend').innerHTML = '<p>載入資料時發生錯誤</p>';
        });
    }

    // 初始載入圖表
    updateTimeSeriesChart();

    // 添加事件監聽器
    document.getElementById('timeRangeSelect').addEventListener('change', updateTimeSeriesChart);
    document.getElementById('filterTypeSelect').addEventListener('change', updateTimeSeriesChart);
    document.getElementById('productFilterSelect').addEventListener('change', updateTimeSeriesChart);

    // 互補商品推薦
    document.getElementById('productSelect').addEventListener('change', function() {
      const productId = this.value;
      if (!productId) {
        document.getElementById('recommendationResult').innerHTML = '';
        return;
      }
      fetch('get_recommendations.php?product_id=' + productId)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.recommendations.length > 0) {
            let html = '<h3>互補商品推薦</h3><ul>';
            data.recommendations.forEach(rec => {
              html += `<li style="list-style-position: inside;"><strong>${rec.product_name}</strong> - 推薦分數: ${rec.score.toFixed(2)}</li>`;
            });
            html += '</ul>';
            document.getElementById('recommendationResult').innerHTML = html;
          } else {
            document.getElementById('recommendationResult').innerHTML = '<p>暫無推薦結果</p>';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('recommendationResult').innerHTML = '<p>載入推薦結果時發生錯誤</p>';
        });
    });

    // 建議清單
    document.getElementById('memberSelect').addEventListener('change', function() {
      const memberId = this.value;
      if (!memberId) {
        document.getElementById('suggestionResult').innerHTML = '';
        return;
      }
      fetch('get_suggestions.php?member_id=' + memberId)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.suggestions.length > 0) {
            let html = '<h3>建議清單</h3><ul>';
            data.suggestions.forEach(sug => {
              html += `<li style="list-style-position: inside;"><strong>${sug.product_name}</strong> - 推薦分數: ${sug.score.toFixed(2)}</li>`;
            });
            html += '</ul>';
            document.getElementById('suggestionResult').innerHTML = html;
          } else {
            document.getElementById('suggestionResult').innerHTML = '<p>暫無建議結果</p>';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('suggestionResult').innerHTML = '<p>載入建議結果時發生錯誤</p>';
        });
    });
  </script>
</body>

</html>