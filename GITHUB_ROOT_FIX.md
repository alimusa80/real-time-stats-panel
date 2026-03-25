# GitHub Root Fix

If GitHub shows a single folder like `real-time-stats-panel/` at the repository root and says `Add a README`, the repository layout is wrong for a package release.

## Correct layout

These files must exist at the repository root:

- `README.md`
- `composer.json`
- `src/`
- `config/`
- `resources/`
- `routes/`

## Wrong layout

This is wrong:

```text
repo-root/
└── real-time-stats-panel/
    ├── README.md
    ├── composer.json
    └── src/
```

This is correct:

```text
repo-root/
├── README.md
├── composer.json
├── src/
├── config/
├── resources/
└── routes/
```

## Why this matters

- GitHub only renders the repository README from the repository root.
- Packagist expects `composer.json` at the repository root.
- Filament plugin distribution expects a normal package repository layout.

## Safe fix in PowerShell

Run these commands inside the cloned GitHub repository, not inside the Laravel app:

```powershell
Get-ChildItem -Force .\real-time-stats-panel | Move-Item -Destination .
Remove-Item -LiteralPath .\real-time-stats-panel -Recurse -Force
git add .
git commit -m "Flatten package to repository root"
git push
```

## If you have not pushed yet

The easiest approach is:

1. Create a fresh GitHub repository.
2. Open the local package folder:
   `packages/alimusa/real-time-stats-panel`
3. Initialize Git there.
4. Push that folder as the repository root.
