<?php

/**
 * tunnaduong/musiclib
 * Copyright 2026 (c) Duong Tung Anh
 * Last modified: 2:59PM - 20/04/2026
 */
include_once("vn-slug.php");

$err = $success = "";

if (isset($_POST['submit'])) {
    $file = $_FILES['song_file'];
    $targetDir = "uploads/songs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // basic properties set
    $originalName = basename($file['name']);
    $songTitle = $_POST['song_name'];
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $newFileName = create_slug($songTitle) . '-' . time() . '.' . $fileExt;
    $fileTmpPath = $file['tmp_name'];
    $targetFilePath = $targetDir . $newFileName;

    // check if song name is present
    if (empty($songTitle))
        die("Ơ kìa, tên nhạc đâu rồi :D<br><a href='/'>Quay lại</a>");

    // need to check if the file really is .mp3/.wav (songs file format)
    $allowedTypes = ['mp3', 'wav'];

    if (!in_array($fileExt, $allowedTypes))
        die("Xin lỗi, định dạng file đếch hợp lệ :D. Chỉ chấp nhận: " . implode(', ', $allowedTypes) . "<br><a href='/'>Quay lại</a>");

    // check for file size larger than 100MB
    if ($file['size'] > 100 * 1024 * 1024)
        die("File nặng thế cha nội -.- !!??<br><a href='/'>Quay lại</a>");

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
        $success = "Upload nhạc thành công! Hãy copy lệnh.";
    } else {
        $error_code = $_FILES['song_file']['error'];
        $err = "Đã có lỗi bí ẩn gì đó xảy ra khi upload nhạc :(<br><code>" . $error_code . "</code>";
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>

<body>
    <center>
        <h1>MusicLib - Thư viện nhạc cho bot Discord</h1>
        <h2>Dev by: Tùng Anh</h2>
        <div style="border: 2px solid black;max-width: 500px;">
            <h3>Upload nhạc lên máy chủ</h3>
            <form action="." method="post" enctype="multipart/form-data">
                <label for="song_name">Tên nhạc:</label>
                <input type="text" id="song_name" name="song_name" required>
                <br>
                <br>
                <label for="song_file">Chọn nhạc:</label>
                <input id="song_file" type="file" name="song_file" accept=".mp3, .wav" required>
                <p style="color: green"><?= $success ?></p>
                <p style="color: red"><?= $err ?></p>
                <button type=" submit" name="submit" style="margin-bottom: 20px">Tải lên</button>
            </form>
        </div>
        <div style="border: 2px solid black;max-width: 500px;margin-top: 20px;padding: 0 20px;">
            <h3>Danh sách nhạc hiện có trên máy chủ</h3>
            <?php
            $json = file_get_contents("data.json");
            $obj = json_decode($json);

            foreach (array_reverse($obj->music_list) as $song) {
                echo "<div style='margin: 10px 0;'>" . $song->name;
                echo " | <a href='javascript:copyCommand(\"" . $song->path . "\");'>Copy lệnh</a> | <a href='" . $song->path . "'>Nghe thử</a></div>";
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
