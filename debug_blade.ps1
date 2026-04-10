
$file = "d:\pos_system\pos_backend\resources\views\expenses\index.blade.php"
$lines = Get-Content $file
$stack = @()
$ln = 1
foreach ($line in $lines) {
    # Match @if only if it's not @endif or @elseif
    if ($line -match "(?<!end|else)@if\b") {
        $stack += $ln
    }
    if ($line -match "@endif\b") {
        if ($stack.Count -eq 0) {
            Write-Host "Extra @endif at line $ln"
        } else {
            $stack = $stack[0..($stack.Count - 2)]
        }
    }
    $ln++
}
if ($stack.Count -gt 0) {
    Write-Host "Unclosed @if at lines: $($stack -join ', ')"
}
