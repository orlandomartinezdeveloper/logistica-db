<#
.SYNOPSIS
    Sincroniza archivos entre el repositorio y XAMPP con ajuste automático de rutas.
.DESCRIPTION
    - Repo -> XAMPP: ajusta config require y rutas de imágenes para local
    - XAMPP -> Repo: ajusta config require y rutas de imágenes para HostGator
    - No toca archivos de imagen, solo PHP, CSS, JS, HTML, SQL, JSON, MD
    - Muestra un resumen de lo que sincronizó
.USAGE
    .\sync.ps1              (menú interactivo)
    .\sync.ps1 -Direction repo-to-xampp
    .\sync.ps1 -Direction xampp-to-repo
#>

param(
    [ValidateSet("repo-to-xampp", "xampp-to-repo")]
    [string]$Direction
)

$repoPath    = "F:\Projeto-Web\calebitotransporte"
$xamppPath   = "C:\xampp\htdocs\calebitotransporte"
$extensions  = @("*.php", "*.css", "*.js", "*.html", "*.sql", "*.json", "*.md")
$excludeDirs = @("img", ".git", "node_modules")

# ============================================================
#  Funciones de ajuste de rutas
# ============================================================

function Convert-RepoToXampp {
    param([string]$content, [string]$RelativePath = "")
    # Config require (subfolder: dashboard/users/ needs ../../config.php)
    if ($RelativePath -like "dashboard\users\*") {
        $content = $content -replace "require\s+'\/home\/calebito\/config\.php'", "require __DIR__ . '/../../config.php'"
    } else {
        $content = $content -replace "require\s+'\/home\/calebito\/config\.php'", "require __DIR__ . '/../config.php'"
    }
    # Imágenes: subfolder (dashboard/users/) → first handle triple
    $content = $content -replace "\.\.\/\.\.\/\.\.\/img\/", "../../img/"
    # Imágenes: standard (dashboard/)
    $content = $content -replace "\.\.\/\.\.\/img\/", "../img/"
    return $content
}

function Convert-XamppToRepo {
    param([string]$content, [string]$RelativePath = "")
    # Config require (both depths)
    $content = $content -replace "require\s+__DIR__\s*\.\s*'\/\.\.\/\.\.\/config\.php'", "require '/home/calebito/config.php'"
    $content = $content -replace "require\s+__DIR__\s*\.\s*'\/\.\.\/config\.php'", "require '/home/calebito/config.php'"
    # Imágenes
    $content = $content -replace "\.\.\/img\/", "../../img/"
    return $content
}

# ============================================================
#  Comparar archivos ignorando diferencias de paths
# ============================================================

function Test-FilesEqual {
    param([string]$Path1, [string]$Path2)
    $c1 = Get-Content $Path1 -Raw -Encoding UTF8
    $c2 = Get-Content $Path2 -Raw -Encoding UTF8
    # Normalizar: aplicar ambas conversiones y comparar
    $norm1 = Convert-RepoToXampp $c1
    $norm2 = Convert-RepoToXampp $c2
    return ($norm1 -eq $norm2)
}

# ============================================================
#  Obtener archivos recursivamente (excluyendo carpetas)
# ============================================================

function Get-FilesRecursive {
    param([string]$Path, [string[]]$Include)
    $allFiles = @()
    foreach ($ext in $Include) {
        $allFiles += Get-ChildItem -Path $Path -Filter $ext -Recurse -File |
            Where-Object {
                $relativePath = $_.FullName.Replace($Path, "").TrimStart("\")
                $excluded = $false
                foreach ($dir in $excludeDirs) {
                    if ($relativePath -like "$dir\*" -or $relativePath -eq $dir) {
                        $excluded = $true
                        break
                    }
                }
                -not $excluded
            }
    }
    return $allFiles
}

# ============================================================
#  Sincronización en una dirección
# ============================================================

function Sync-Direction {
    param(
        [string]$Source,
        [string]$Destination,
        [string]$DirectionName,
        [scriptblock]$Converter
    )

    $sourceFiles = Get-FilesRecursive -Path $Source -Include $extensions
    $created  = @()
    $updated  = @()
    $skipped  = @()

    foreach ($file in $sourceFiles) {
        $relativePath = $file.FullName.Replace($Source, "").TrimStart("\")
        $destFile = Join-Path $Destination $relativePath
        $destDir = Split-Path $destFile -Parent

        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }

        if (Test-Path $destFile) {
            if (Test-FilesEqual $file.FullName $destFile) {
                $skipped += $relativePath
                continue
            }
            $status = "ACTUALIZADO"
        } else {
            $status = "CREADO"
        }

        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $newContent = & $Converter $content $relativePath
        [System.IO.File]::WriteAllText($destFile, $newContent, (New-Object System.Text.UTF8Encoding $false))

        if ($status -eq "CREADO") { $created += $relativePath }
        else { $updated += $relativePath }
    }

    # Resultados
    Write-Host "`n=== $DirectionName ===" -ForegroundColor Cyan
    if ($created.Count -gt 0) {
        Write-Host "`n  Creados ($($created.Count)):" -ForegroundColor Green
        foreach ($f in $created) { Write-Host "    + $f" -ForegroundColor Green }
    }
    if ($updated.Count -gt 0) {
        Write-Host "`n  Actualizados ($($updated.Count)):" -ForegroundColor Yellow
        foreach ($f in $updated) { Write-Host "    ~ $f" -ForegroundColor Yellow }
    }
    if ($created.Count -eq 0 -and $updated.Count -eq 0) {
        Write-Host "`n  Todo sincronizado, sin cambios." -ForegroundColor DarkGray
    } else {
        Write-Host "`n  Sin cambios ($($skipped.Count)): OK" -ForegroundColor DarkGray
    }
}

# ============================================================
#  Menú
# ============================================================

if (-not $Direction) {
    Write-Host "`n=== Sincronizar Proyecto ===" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  1. Repo -> XAMPP  (subir cambios del repositorio al local)"
    Write-Host "  2. XAMPP -> Repo  (bajar cambios del local al repositorio)"
    Write-Host ""
    $choice = Read-Host "Selecciona (1 o 2)"

    switch ($choice) {
        "1" { $Direction = "repo-to-xampp" }
        "2" { $Direction = "xampp-to-repo" }
        default { Write-Host "Opcion invalida." -ForegroundColor Red; exit }
    }
}

# ============================================================
#  Ejecutar
# ============================================================

Write-Host "`nIniciando sincronizacion..." -ForegroundColor Cyan

if ($Direction -eq "repo-to-xampp") {
    Sync-Direction -Source $repoPath -Destination $xamppPath -DirectionName "Repo -> XAMPP" -Converter { Convert-RepoToXampp $args[0] $args[1] }
} else {
    Sync-Direction -Source $xamppPath -Destination $repoPath -DirectionName "XAMPP -> Repo" -Converter { Convert-XamppToRepo $args[0] $args[1] }
}

Write-Host "`nSincronizacion completada." -ForegroundColor Cyan
