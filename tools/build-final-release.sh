#!/usr/bin/env bash
set -euo pipefail

version="1.3.0"
package_name="23-sabri-doctor-founder-publishing-dashboard-${version}.zip"
manifest_name="FILE23-${version}-MANIFEST.sha256"
source_root="23-Doctor-Founder-Publishing-Dashboard-Source-${version}"
source_package="${source_root}.zip"
source_manifest="FILE23-${version}-SOURCE-MANIFEST.sha256"

build_package() {
  local root="$1"
  rm -rf "$root" "$root.zip"
  mkdir -p "$root/sabri-publishing-dashboard"
  # The installable ZIP must include the exact Composer-locked Unicode
  # PDF engine; publishing a source-only plugin would silently disable PDF.
  test -s composer.json
  test -s composer.lock
  test -s vendor/autoload.php
  test -s vendor/mpdf/mpdf/src/Mpdf.php
  cp sabri-publishing-dashboard.php readme.txt uninstall.php composer.json composer.lock "$root/sabri-publishing-dashboard/"
  for dir in assets includes templates languages vendor; do
    if [ -d "$dir" ]; then cp -a "$dir" "$root/sabri-publishing-dashboard/"; fi
  done
  find "$root" -type d -exec chmod 0755 {} +
  find "$root" -type f -exec chmod 0644 {} +
  find "$root" -exec touch -h -t 202608100800.00 {} +
  (
    cd "$root"
    LC_ALL=C find sabri-publishing-dashboard -type f -print | LC_ALL=C sort > ../package-files.txt
    zip -X -q "../${root}.zip" -@ < ../package-files.txt
  )
}

rm -f "$package_name" "$package_name.sha256" "$manifest_name" \
  "$source_package" "$source_package.sha256" "$source_manifest"

build_package build-one
build_package build-two
cmp build-one.zip build-two.zip
mv build-one.zip "$package_name"
unzip -t "$package_name"
test "$(unzip -Z1 "$package_name" | head -n1 | cut -d/ -f1)" = 'sabri-publishing-dashboard'
unzip -p "$package_name" sabri-publishing-dashboard/sabri-publishing-dashboard.php | grep -Eq '^ \* Version:[[:space:]]+1\.3\.0$'
unzip -p "$package_name" sabri-publishing-dashboard/readme.txt | grep -Fq 'Stable tag: 1.3.0'
unzip -Z1 "$package_name" | grep -Fxq 'sabri-publishing-dashboard/vendor/autoload.php'
unzip -Z1 "$package_name" | grep -Fxq 'sabri-publishing-dashboard/vendor/mpdf/mpdf/src/Mpdf.php'
unzip -Z1 "$package_name" | grep -Fxq 'sabri-publishing-dashboard/vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf'
unzip -Z1 "$package_name" | grep -Fxq 'sabri-publishing-dashboard/vendor/mpdf/mpdf/ttfonts/XB Riyaz.ttf'
unzip -Z1 "$package_name" | grep -Fxq 'sabri-publishing-dashboard/vendor/mpdf/mpdf/LICENSE.txt'
unzip -p "$package_name" sabri-publishing-dashboard/composer.lock | grep -Fq '"name": "mpdf/mpdf"'
sha256sum "$package_name" > "$package_name.sha256"
(
  cd build-two/sabri-publishing-dashboard
  find . -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum
) > "$manifest_name"

# Complete reviewed source package: every Git-tracked source, workflow, test and document.
git archive --format=zip --prefix="${source_root}/" -o "$source_package" HEAD
unzip -t "$source_package"
test "$(unzip -Z1 "$source_package" | head -n1 | cut -d/ -f1)" = "$source_root"
unzip -p "$source_package" "${source_root}/sabri-publishing-dashboard.php" | grep -Eq '^ \* Version:[[:space:]]+1\.3\.0$'
unzip -p "$source_package" "${source_root}/docs/RELEASE-SIGNOFF.md" | grep -Fq 'PENDING — DO NOT MERGE OR DEPLOY'
sha256sum "$source_package" > "$source_package.sha256"
git ls-files -z | LC_ALL=C sort -z | xargs -0 sha256sum > "$source_manifest"

rm -rf build-one build-two build-two.zip package-files.txt
