$ErrorActionPreference='Continue'

# A) MAIN
$mainCommit='NONE'; $mainPush='SKIPPED'
$gitProcs=Get-Process | Where-Object { $_.ProcessName -like 'git*' }
if($gitProcs){ Stop-Process -Id $gitProcs.Id -Force -ErrorAction SilentlyContinue }
if(Test-Path '.git/index.lock'){ Remove-Item '.git/index.lock' -Force -ErrorAction SilentlyContinue }

git config core.longpaths true | Out-Null
git add -A -- . ':(exclude)Webs2026/metaverso/wp-content/uploads/**' 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_MAIN_ADD=" + $_) } }
git diff --cached --quiet
if($LASTEXITCODE -ne 0){
  git commit -m 'chore: sync today updates' 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_MAIN_COMMIT=" + $_) } }
  if($LASTEXITCODE -eq 0){
    $mainCommit=(git rev-parse --short HEAD).Trim()
    git push 2>&1 | Tee-Object -Variable mainPushOut | Out-Null
    if($LASTEXITCODE -eq 0){ $mainPush='OK' } else { $mainPush='FAIL'; ($mainPushOut | Select-Object -First 2) | ForEach-Object { Write-Output ("ERROR_MAIN_PUSH=" + $_) } }
  } else { $mainPush='FAIL' }
}
$mainBranch=(git branch --show-current).Trim()
$mainHead=(git rev-parse --short HEAD).Trim()
$mainDirty='NO'; if(-not [string]::IsNullOrWhiteSpace((git status --porcelain | Out-String))){ $mainDirty='YES' }
Write-Output ("MAIN_BRANCH=" + $mainBranch)
Write-Output ("MAIN_HEAD=" + $mainHead)
Write-Output ("MAIN_COMMIT_HASH=" + $mainCommit)
Write-Output ("MAIN_PUSH=" + $mainPush)
Write-Output ("MAIN_DIRTY=" + $mainDirty)

# B) APP
Set-Location 'J:\Empres\MetaversOtec\Desarrollos\App'
$appCommit='NONE'; $appPush='SKIPPED'
$gitProcs=Get-Process | Where-Object { $_.ProcessName -like 'git*' }
if($gitProcs){ Stop-Process -Id $gitProcs.Id -Force -ErrorAction SilentlyContinue }
if(Test-Path '.git/index.lock'){ Remove-Item '.git/index.lock' -Force -ErrorAction SilentlyContinue }

git config core.longpaths true | Out-Null
git add -A 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_APP_ADD=" + $_) } }
git diff --cached --quiet
if($LASTEXITCODE -ne 0){
  git commit -m 'chore: sync today updates' 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_APP_COMMIT=" + $_) } }
  if($LASTEXITCODE -eq 0){
    $appCommit=(git rev-parse --short HEAD).Trim()
    git push 2>&1 | Tee-Object -Variable appPushOut | Out-Null
    if($LASTEXITCODE -eq 0){
      $appPush='OK'
    } else {
      $pushText=($appPushOut | Out-String)
      if($pushText -match 'non-fast-forward|rejected'){
        git pull --rebase origin main 2>&1 | Tee-Object -Variable appRebaseOut | Out-Null
        if($LASTEXITCODE -eq 0){
          git push 2>&1 | Tee-Object -Variable appPushOut2 | Out-Null
          if($LASTEXITCODE -eq 0){ $appPush='OK' } else { $appPush='FAIL'; ($appPushOut2 | Select-Object -First 2) | ForEach-Object { Write-Output ("ERROR_APP_PUSH=" + $_) } }
        } else { $appPush='FAIL'; ($appRebaseOut | Select-Object -First 2) | ForEach-Object { Write-Output ("ERROR_APP_REBASE=" + $_) } }
      } else { $appPush='FAIL'; ($appPushOut | Select-Object -First 2) | ForEach-Object { Write-Output ("ERROR_APP_PUSH=" + $_) } }
    }
  } else { $appPush='FAIL' }
}
$appBranch=(git branch --show-current).Trim()
$appHead=(git rev-parse --short HEAD).Trim()
$appDirty='NO'; if(-not [string]::IsNullOrWhiteSpace((git status --porcelain | Out-String))){ $appDirty='YES' }
Write-Output ("APP_BRANCH=" + $appBranch)
Write-Output ("APP_HEAD=" + $appHead)
Write-Output ("APP_COMMIT_HASH=" + $appCommit)
Write-Output ("APP_PUSH=" + $appPush)
Write-Output ("APP_DIRTY=" + $appDirty)

# C) PUNTERO SUBMODULO
Set-Location 'J:\Empres\MetaversOtec\Desarrollos'
$pointerCommit='NONE'; $pointerPush='SKIPPED'
$subStatus=(git status --short -- App | Out-String).Trim()
if(-not [string]::IsNullOrWhiteSpace($subStatus)){
  git add App 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_POINTER_ADD=" + $_) } }
  git diff --cached --quiet
  if($LASTEXITCODE -ne 0){
    git commit -m 'chore: update App submodule pointer' 2>&1 | ForEach-Object { if($_ -match 'error|fatal'){ Write-Output ("ERROR_POINTER_COMMIT=" + $_) } }
    if($LASTEXITCODE -eq 0){
      $pointerCommit=(git rev-parse --short HEAD).Trim()
      git push 2>&1 | Tee-Object -Variable pointerPushOut | Out-Null
      if($LASTEXITCODE -eq 0){ $pointerPush='OK' } else { $pointerPush='FAIL'; ($pointerPushOut | Select-Object -First 2) | ForEach-Object { Write-Output ("ERROR_POINTER_PUSH=" + $_) } }
    } else { $pointerPush='FAIL' }
  }
}
$finalMainDirty='NO'; if(-not [string]::IsNullOrWhiteSpace((git status --porcelain | Out-String))){ $finalMainDirty='YES' }
Write-Output ("POINTER_COMMIT_HASH=" + $pointerCommit)
Write-Output ("POINTER_PUSH=" + $pointerPush)
Write-Output ("FINAL_MAIN_DIRTY=" + $finalMainDirty)
