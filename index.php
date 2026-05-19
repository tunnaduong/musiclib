<?php

/**
 * tunnaduong/musiclib
 * Copyright 2026 (c) Duong Tung Anh
 * Last modified: 4:15AM - 20/05/2026
 */

// ==========================================
// 🛠️ nới xích cho php để chống timeout và nghẽn file lớn
set_time_limit(0);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
// ==========================================

include_once("vn-slug.php");

$err = $success = "";

// check xem request có phải ajax gửi lên không để trả về json gọn nhẹ
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

if (isset($_FILES['song_file'])) {
    $file = $_FILES['song_file'];
    $targetDir = "uploads/songs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // basic properties set
    $originalName = basename($file['name']);
    $songTitle = $_POST['song_name'] ?? '';
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $newFileName = create_slug($songTitle) . '-' . time() . '.' . $fileExt;
    $fileTmpPath = $file['tmp_name'];
    $targetFilePath = $targetDir . $newFileName;

    // check if song name is present
    if (empty($songTitle)) {
        if ($isAjax) { echo json_encode(["status" => "error", "msg" => "Ơ kìa, tên nhạc đâu rồi :D"]); exit; }
        die("Ơ kìa, tên nhạc đâu rồi :D<br><a href='/'>Quay lại</a>");
    }

    // need to check if the file really is .mp3/.wav/.m4a (songs file format)
    $allowedTypes = ['mp3', 'wav', 'm4a'];

    if (!in_array($fileExt, $allowedTypes)) {
        if ($isAjax) { echo json_encode(["status" => "error", "msg" => "Xin lỗi, định dạng file đếch hợp lệ :D. Chỉ chấp nhận: " . implode(', ', $allowedTypes)]); exit; }
        die("Xin lỗi, định dạng file đếch hợp lệ :D. Chỉ chấp nhận: " . implode(', ', $allowedTypes) . "<br><a href='/'>Quay lại</a>");
    }

    // check for file size larger than 100MB
    if ($file['size'] > 100 * 1024 * 1024) {
        if ($isAjax) { echo json_encode(["status" => "error", "msg" => "File nặng thế cha nội -.- !!??"]); exit; }
        die("File nặng thế cha nội -.- !!??<br><a href='/'>Quay lại</a>");
    }

    // move the file from temporary to permanent location
    if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
        // save the uploaded song to database
        $old_data = file_exists("data.json") ? file_get_contents("data.json") : "";
        $_obj = json_decode($old_data, true);

        // if the file is empty or corrupted, create new list
        if (!isset($_obj['music_list'])) {
            $_obj = ['music_list' => []];
        }

        // prepare new song data (match the data.json format)
        $newSong = [
            "path" => "/" . $targetFilePath,
            "name" => $songTitle
        ];

        // add new element to 'music_list' array
        $_obj['music_list'][] = $newSong;

        // encode and save back to data.json
        file_put_contents("data.json", json_encode($_obj, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        if ($isAjax) {
            echo json_encode(["status" => "success", "msg" => "Upload nhạc thành công! Hãy copy lệnh."]);
            exit;
        }
        $success = "Upload nhạc thành công! Hãy copy lệnh.";
    } else {
        $error_code = $_FILES['song_file']['error'];
        $errMsg = "Đã có lỗi bí ẩn gì đó xảy ra khi upload nhạc :(<br><code>" . $error_code . "</code>";
        if ($isAjax) { echo json_encode(["status" => "error", "msg" => $errMsg]); exit; }
        $err = $errMsg;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicLib - Thư viện nhạc cho bot Discord</title>
    <link rel="icon" type="image/png" href="./icon.png">
</head>

<body>
    <center>
        <img src="./cat.png" alt="cute hong?">
        <h1 style="margin:0">MusicLib - Thư viện nhạc cho bot Discord</h1>
        <h2>Dev by: Tùng Anh</h2>
        <div style="border: 2px solid black;max-width: 500px;">
            <h3>Upload nhạc lên máy chủ</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax" value="1">
                <label for="song_name">Tên nhạc:</label>
                <input type="text" id="song_name" name="song_name" required>
                <br>
                <br>
                <label for="song_file">Chọn nhạc:</label>
                <input id="song_file" type="file" name="song_file" accept=".mp3, .wav, .m4a" required>
                <p id="msg_success" style="color: green"><?= $success ?></p>
                <p id="msg_err" style="color: red"><?= $err ?></p>
                <button type="submit" id="submitBtn" style="margin-bottom: 20px">Tải lên</button>
            </form>
        </div>
        <div style="border: 2px solid black;max-width: 500px;margin-top: 20px;padding: 0 20px;box-sizing: border-box;">
            <h3>Danh sách nhạc hiện có trên máy chủ</h3>
            <?php
            $json = file_exists("data.json") ? file_get_contents("data.json") : '{"music_list":[]}';
            $obj = json_decode($json);

            if (isset($obj->music_list) && is_array($obj->music_list)) {
                foreach (array_reverse($obj->music_list) as $song) {
                    echo "<div style='margin: 10px 0;'>" . htmlspecialchars($song->name);
                    echo " | <a href='javascript:copyCommand(\"" . htmlspecialchars($song->path) . "\");'>Copy lệnh</a> | <a href='" . htmlspecialchars($song->path) . "'>Nghe thử</a></div>";
                }
            }
            ?>
            <div style="margin-top: 20px"></div>
        </div>
        <br>
        <a href="https://discord.gg/RGk36c3Jrs" target="_blank">
            <img src="./icon.png" alt="cute hong?">
            <br>
            Tâm Sự Hong? Discord Server
        </a>
        <br>
        <a href="https://github.com/tunnaduong/musiclib" target="_blank">GitHub</a>
    </center>
    <script>
        // xử lý upload bằng fetch để né lỗi timeout 524 cloudflare
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitBtn');
            const successText = document.getElementById('msg_success');
            const errText = document.getElementById('msg_err');
            
            // clear thông báo cũ, đổi trạng thái nút
            successText.innerText = "";
            errText.innerHTML = "Đang tải file lên máy chủ, vui lòng đợi tí bro... Đừng tắt trang nhé 😎";
            submitBtn.disabled = true;
            
            fetch('.', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    alert("Ngon nghẻ rồi! " + data.msg);
                    window.location.reload(); // reload để cập nhật bài mới vô danh sách
                } else {
                    errText.innerHTML = data.msg;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error("Lỗi:", error);
                errText.innerHTML = "Vẫn dính lỗi gì đó rồi bro ơi, check log nginx hoặc console trình duyệt xem sao.";
                submitBtn.disabled = false;
            });
        });

        async function copyCommand(path) {
            try {
                await navigator.clipboard.writeText("m!p " + window.location.origin + path);
                alert("Đã copy lệnh! Hãy paste vào kênh thoại #music nhé! Chúc bồ tèo một ngày tốt lành! :D");
            } catch (err) {
                const textArea = document.createElement("textarea");
                textArea.value = "m!p " + window.location.origin + path;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert("Đã copy lệnh! Hãy paste vào kênh thoại #music nhé! Chúc bồ tèo một ngày tốt lành! :D");
                } catch (err) {
                    alert("Oops! Đã có lỗi gì đó xảy ra, hãy báo lỗi cho Tùng Anh nhé :(\nChi tiết lỗi: " + err);
                }
                document.body.removeChild(textArea);
            }
        }
    </script>
</body>

</html>