@echo off
title Pembaruan Aplikasi EDMS RSGM
color 0A

echo ===================================================
echo Memeriksa dan Mengunduh Pembaruan Terbaru...
echo ===================================================
echo.

:: Memaksa sinkronisasi dengan GitHub dan mengabaikan perubahan lokal yang tidak disengaja
git fetch origin main
git reset --hard origin/main

echo.
echo ===================================================
echo Selesai! Jika ada pembaruan, aplikasi sudah ter-update.
echo Jika muncul "Already up to date", berarti sudah versi terbaru.
echo ===================================================
echo.
pause
