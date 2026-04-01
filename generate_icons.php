<?php
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
foreach ($sizes as $s) {
    $img = imagecreatetruecolor($s, $s);
    imagesavealpha($img, true);
    $bg = imagecolorallocate($img, 10, 15, 5);
    $green1 = imagecolorallocate($img, 132, 204, 22);
    $green2 = imagecolorallocate($img, 163, 230, 53);
    $green3 = imagecolorallocate($img, 190, 242, 100);
    imagefill($img, 0, 0, $bg);

    $cx = (int)($s / 2);
    $cy = (int)($s / 2);
    $t = max(2, (int)($s * 0.04));
    $off = (int)($s * 0.10);

    imagesetthickness($img, $t);
    imagearc($img, $cx, $cy + $off * 2, (int)($s * 0.55), (int)($s * 0.35), 180, 360, $green1);
    imagearc($img, $cx, $cy + $off, (int)($s * 0.50), (int)($s * 0.45), 180, 360, $green2);
    imagearc($img, $cx, $cy, (int)($s * 0.45), (int)($s * 0.55), 180, 360, $green3);

    imagepng($img, __DIR__ . '/assets/icons/icon-' . $s . '.png');
    imagedestroy($img);
    echo "Generated: icon-{$s}.png\n";
}
echo "Done!\n";
