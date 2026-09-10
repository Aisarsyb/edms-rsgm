@echo off
title Pembaruan Aplikasi EDMS RSGM
color 0A

echo ===================================================
echo Memeriksa dan Mengunduh Pembaruan Terbaru...
echo ===================================================
echo.

:: Menjalankan perintah git pull untuk menarik pembaruan
git pull origin main

echo.
echo ===================================================
echo Selesai! Jika ada pembaruan, aplikasi sudah ter-update.
echo Jika muncul "Already up to date", berarti sudah versi terbaru.
echo ===================================================
echo.
pause
