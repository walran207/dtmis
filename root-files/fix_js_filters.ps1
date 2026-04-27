$file = "c:\xampp\htdocs\edats\app\templates\_role_scripts.php"
$content = Get-Content $file -Raw

# Update Pending Approval mapping
$oldApproval = "labelKey.indexOf\('pending approval'\) !== -1\s*\|\|\s*labelKey.indexOf\('for validation'\) !== -1\s*\|\|\s*labelKey.indexOf\('to validate'\) !== -1\s*\)\s*\{\s*return \['received', 'pending'\];"
$newApproval = "labelKey.indexOf('pending approval') !== -1 || labelKey.indexOf('for validation') !== -1 || labelKey.indexOf('to validate') !== -1) { return ['approved', 'received', 'pending'];"
$content = $content -replace $oldApproval, $newApproval

# Update Pending Forward mapping
$oldForward = "if \(labelKey.indexOf\('pending forward'\) !== -1\) \{\s*return \['approved', 'pending', 'forward'\];"
$newForward = "if (labelKey.indexOf('pending forward') !== -1) { return ['forward', 'approved', 'pending'];"
$content = $content -replace $oldForward, $newForward

# Fix the 'recieved' typo in induction logic
$content = $content -replace "pending recieved", "pending received"

Set-Content $file $content
