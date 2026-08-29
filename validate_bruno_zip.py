import os
import zipfile
import tempfile
import json

zip_path = "/home/admin/web/donkeydeliveries.com/public_html/donkey/bruno/Donkey_Vendor_API_Bruno_Collection.zip"

with tempfile.TemporaryDirectory() as tmpdir:
    with zipfile.ZipFile(zip_path, 'r') as zipf:
        zipf.extractall(tmpdir)
        names = zipf.namelist()

    print(f"Extracted {len(names)} entries from ZIP.")
    
    # Check bruno.json
    bj_path = os.path.join(tmpdir, "Donkey Vendor API", "bruno.json")
    assert os.path.exists(bj_path), "bruno.json missing!"
    with open(bj_path) as f:
        bj_data = json.load(f)
    print(f"bruno.json valid: {bj_data}")

    # Check environments/local.bru
    env_path = os.path.join(tmpdir, "Donkey Vendor API", "environments", "local.bru")
    assert os.path.exists(env_path), "local.bru missing!"
    print("local.bru verified.")

    # Check README and API_AUDIT
    readme_p = os.path.join(tmpdir, "README.md")
    audit_p = os.path.join(tmpdir, "API_AUDIT.md")
    assert os.path.exists(readme_p), "README.md missing!"
    assert os.path.exists(audit_p), "API_AUDIT.md missing!"
    print("README.md and API_AUDIT.md verified.")

    # Count .bru files
    bru_files = [n for n in names if n.endswith('.bru') and 'environments' not in n]
    print(f"Total API request .bru files verified: {len(bru_files)}")

print("ZIP Validation SUCCESSFUL!")
