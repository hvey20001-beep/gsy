<?php
// 配置部分
$baseDir = 'sj'; // 图片存储根目录
$categories = ['无', '木', '火', '土', '金', '水', '木火', '火土', '土金', '金水', '水木'];
$deletePassword = '88888888';

// --- 后端逻辑处理区域 ---

// 1. 处理图片上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['crystalImage'])) {
    $category = $_POST['category'] ?? '无';
    if (!in_array($category, $categories)) $category = '无';
    
    $targetDir = $baseDir . '/' . $category . '/';
    
    // 自动创建目录
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['crystalImage']['name']);
    $targetFile = $targetDir . $fileName;
    
    // 移动文件
    if (move_uploaded_file($_FILES['crystalImage']['tmp_name'], $targetFile)) {
        echo "<script>alert('上传成功！'); window.location.href='sj.php';</script>";
    } else {
        echo "<script>alert('上传失败，请检查文件夹权限。');</script>";
    }
    exit;
}

// 2. 处理图片删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $filePath = $_POST['filePath'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($password === $deletePassword) {
        // 安全检查：防止删除非 sj 目录下的文件
        if (strpos($filePath, $baseDir) === 0 && file_exists($filePath)) {
            unlink($filePath);
            echo json_encode(['status' => 'success', 'message' => '删除成功']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '文件不存在或路径非法']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '密码错误']);
    }
    exit;
}

// 3. 读取现有图片用于展示
$galleryData = [];
foreach ($categories as $cat) {
    $dir = $baseDir . '/' . $cat . '/';
    $galleryData[$cat] = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                // 按修改时间倒序排列（简单的）
                $galleryData[$cat][] = $dir . $file;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>水晶展示馆</title>
    <style>
        :root {
            --primary-purple: #6a1b9a;
            --light-purple: #9c4dcc;
            --bg-dark: #120524;
            --crystal-glass: rgba(255, 255, 255, 0.1);
            --border-glow: 0 0 10px #d500f9;
        }

        body {
            background-color: var(--bg-dark);
            background-image: linear-gradient(135deg, #120524 0%, #2a0e3b 100%);
            color: #fff;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding-bottom: 80px; /* 为底部留空 */
            min-height: 100vh;
        }

        /* 顶部导航 */
        .top-nav {
            padding: 15px;
            background: rgba(42, 14, 59, 0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--light-purple);
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
        }

        .back-btn {
            text-decoration: none;
            color: #fff;
            background: linear-gradient(45deg, var(--primary-purple), var(--light-purple));
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            box-shadow: 0 0 5px var(--light-purple);
            transition: transform 0.2s;
        }
        .back-btn:active { transform: scale(0.95); }

        .page-title {
            flex-grow: 1;
            text-align: center;
            margin: 0;
            text-shadow: 0 0 10px #e1bee7;
        }

        /* 容器通用样式 */
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            background: var(--crystal-glass);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        /* 第一部分：上传区域 */
        .upload-section h2 {
            border-bottom: 1px solid var(--light-purple);
            padding-bottom: 10px;
            margin-top: 0;
        }

        .form-group { margin-bottom: 15px; }
        
        select, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--light-purple);
            color: #fff;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .upload-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #7b1fa2, #ba68c8);
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 0 10px var(--primary-purple);
        }

        /* 第二部分：展示区域 */
        .category-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .filter-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--light-purple);
            color: #e1bee7;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 14px;
        }

        .filter-btn.active {
            background: var(--light-purple);
            color: white;
            box-shadow: 0 0 8px #d500f9;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); /* 手机上两列或三列 */
            gap: 10px;
        }
        
        /* 图片容器 */
        .img-card {
            aspect-ratio: 1; /* 正方形 */
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .img-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .img-card:active { transform: scale(0.98); }

        /* 悬浮侧边栏 */
        .sidebar {
            position: fixed;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(42, 14, 59, 0.95);
            border-left: 2px solid var(--light-purple);
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
            padding: 10px 5px;
            z-index: 90;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: -2px 0 10px rgba(0,0,0,0.5);
        }

        .sidebar-btn {
            writing-mode: vertical-rl; /* 竖排文字 */
            text-orientation: upright;
            background: transparent;
            border: none;
            color: #ce93d8;
            padding: 8px 4px;
            font-size: 12px;
            letter-spacing: 2px;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .sidebar-btn.active {
            background: var(--light-purple);
            color: white;
        }

        /* 弹窗样式 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 200;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #2a0e3b;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--light-purple);
            width: 80%;
            max-width: 300px;
            text-align: center;
            box-shadow: 0 0 20px var(--primary-purple);
        }

        .modal-btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-copy { color: #81c784; }
        .btn-save { color: #64b5f6; } /* 注意：Web端无法直接保存到相册，只能提示长按 */
        .btn-delete { color: #e57373; }
        .btn-cancel { background: #4a4a4a; color: #ccc; }

    </style>
</head>
<body>

    <!-- 顶部导航 -->
    <div class="top-nav">
        <a href="index.html" class="back-btn">← 返回首页</a>
        <h1 class="page-title">🔮 水晶展示</h1>
    </div>

    <!-- 右侧悬浮条 -->
    <div class="sidebar">
        <?php foreach ($categories as $cat): ?>
            <button class="sidebar-btn" onclick="showCategory('<?php echo $cat; ?>')"><?php echo $cat; ?></button>
        <?php endforeach; ?>
    </div>

    <!-- 第一部分：上传 -->
    <div class="container upload-section">
        <h2>✨ 添加水晶</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>选择分类：</label>
                <select name="category">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>选择图片：</label>
                <input type="file" name="crystalImage" accept="image/*" required>
            </div>
            <button type="submit" class="upload-btn">上传并保存</button>
        </form>
    </div>

    <!-- 第二部分：展示 -->
    <div class="container display-section">
        <div class="category-filter">
            <?php foreach ($categories as $index => $cat): ?>
                <button class="filter-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                        onclick="showCategory('<?php echo $cat; ?>')"
                        id="btn-<?php echo $cat; ?>">
                    <?php echo $cat; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="gallery-grid" id="galleryGrid">
            <!-- 图片将通过JS动态插入 -->
        </div>
    </div>

    <!-- 操作弹窗 -->
    <div class="modal-overlay" id="actionModal">
        <div class="modal-content">
            <h3 style="margin-top:0; color:#e1bee7">操作选项</h3>
            <img id="modalImgPreview" src="" style="width:100px; height:100px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
            <input type="hidden" id="currentFilePath">
            
            <button class="modal-btn btn-copy" onclick="copyImage()">📋 复制图片链接</button>
            <button class="modal-btn btn-save" onclick="saveTip()">💾 保存到相册</button>
            <button class="modal-btn btn-delete" onclick="deleteImage()">🗑️ 删除图片</button>
            <button class="modal-btn btn-cancel" onclick="closeModal()">取消</button>
        </div>
    </div>

    <script>
        // PHP 数据传给 JS
        const galleryData = <?php echo json_encode($galleryData); ?>;
        let currentCategory = '无';

        // 初始化
        document.addEventListener('DOMContentLoaded', () => {
            showCategory('无');
        });

        // 切换分类展示
        function showCategory(cat) {
            currentCategory = cat;
            
            // 更新顶部按钮状态
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            const topBtn = document.getElementById('btn-' + cat);
            if(topBtn) topBtn.classList.add('active');

            // 更新侧边栏状态
            document.querySelectorAll('.sidebar-btn').forEach(btn => {
                btn.classList.remove('active');
                if(btn.innerText === cat) btn.classList.add('active');
            });

            const grid = document.getElementById('galleryGrid');
            grid.innerHTML = '';

            const images = galleryData[cat];
            if (images && images.length > 0) {
                images.forEach(imgSrc => {
                    const div = document.createElement('div');
                    div.className = 'img-card';
                    div.innerHTML = `<img src="${imgSrc}" loading="lazy" alt="水晶">`;
                    div.onclick = () => openModal(imgSrc);
                    grid.appendChild(div);
                });
            } else {
                grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:#ccc;">暂无水晶图片</div>';
            }
        }

        // 打开弹窗
        const modal = document.getElementById('actionModal');
        const modalImg = document.getElementById('modalImgPreview');
        const filePathInput = document.getElementById('currentFilePath');

        function openModal(src) {
            modal.style.display = 'flex';
            modalImg.src = src;
            filePathInput.value = src;
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // 1. 复制图片链接 (浏览器安全限制通常不允许直接复制图片文件流，这里复制完整链接)
        function copyImage() {
            const url = window.location.origin + window.location.pathname.replace('sj.php', '') + filePathInput.value;
            navigator.clipboard.writeText(url).then(() => {
                alert('图片链接已复制！您可以发送给朋友。');
                closeModal();
            }).catch(err => {
                alert('复制失败，请手动长按图片复制。');
            });
        }

        // 2. 保存到相册 (Web页面无法强制写入相册，必须提示用户操作)
        function saveTip() {
            alert('请长按预览图或网页中的图片，选择“添加到照片”或“保存图片”。这是手机浏览器的安全规定。');
        }

        // 3. 删除图片
        function deleteImage() {
            const pwd = prompt("请输入删除密码以确认操作：");
            if (pwd === null) return; // 点击取消

            const filePath = filePathInput.value;
            
            // 发送 AJAX 请求给 PHP
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('filePath', filePath);
            formData.append('password', pwd);

            fetch('sj.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('图片已删除');
                    closeModal();
                    // 刷新页面以更新列表
                    location.reload(); 
                } else {
                    alert('删除失败：' + data.message);
                }
            })
            .catch(error => {
                alert('系统错误，请重试');
            });
        }
        
        // 点击弹窗外部关闭
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
