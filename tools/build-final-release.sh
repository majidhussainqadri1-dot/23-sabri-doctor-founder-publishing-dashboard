#!/usr/bin/env bash
set -euo pipefail

version="1.1.0"
package_name="23-sabri-doctor-founder-publishing-dashboard-${version}.zip"
manifest_name="FILE23-${version}-MANIFEST.sha256"

build_package() {
  local root="$1"
  rm -rf "$root" "$root.zip"
  mkdir -p "$root/sabri-publishing-dashboard"
  cp sabri-publishing-dashboard.php readme.txt uninstall.php "$root/sabri-publishing-dashboard/"
  for dir in assets includes templates languages; do
    if [ -d "$dir" ]; then cp -a "$dir" "$root/sabri-publishing-dashboard/"; fi
  done
  find "$root" -type d -exec chmod 0755 {} +
  find "$root" -type f -exec chmod 0644 {} +
  find "$root" -exec touch -h -t 202608040330.00 {} +
  (
    cd "$root"
    LC_ALL=C find sabri-publishing-dashboard -type f -print | LC_ALL=C sort > ../package-files.txt
    zip -X -q "../${root}.zip" -@ < ../package-files.txt
  )
}

build_package build-one
build_package build-two
cmp build-one.zip build-two.zip
mv build-one.zip "$package_name"
unzip -t "$package_name"
test "$(unzip -Z1 "$package_name" | head -n1 | cut -d/ -f1)" = 'sabri-publishing-dashboard'
unzip -p "$package_name" sabri-publishing-dashboard/sabri-publishing-dashboard.php | grep -Eq '^ \* Version:[[:space:]]+1\.1\.0$'
unzip -p "$package_name" sabri-publishing-dashboard/readme.txt | grep -Fq 'Stable tag: 1.1.0'
sha256sum "$package_name" > "$package_name.sha256"
(
  cd build-two/sabri-publishing-dashboard
  find . -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum
) > "$manifest_name"
rm -rf build-one build-two build-two.zip package-files.txt
