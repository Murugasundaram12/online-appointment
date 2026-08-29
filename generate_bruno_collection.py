import os
import json
import zipfile

base_dir = "/home/admin/web/donkeydeliveries.com/public_html/donkey/bruno"
collection_name = "Donkey Vendor API"
coll_dir = os.path.join(base_dir, collection_name)
env_dir = os.path.join(coll_dir, "environments")

os.makedirs(env_dir, exist_ok=True)

# 1. bruno.json
bruno_json = {
    "version": "1",
    "name": collection_name,
    "type": "collection",
    "ignore": [
        "node_modules",
        ".git"
    ]
}

with open(os.path.join(coll_dir, "bruno.json"), "w") as f:
    json.dump(bruno_json, f, indent=2)

# 2. local.bru environment
local_bru_content = """name: local
type: environment

vars {
  baseUrl: http://127.0.0.1:8000
  token: 
}
"""

with open(os.path.join(env_dir, "local.bru"), "w") as f:
    f.write(local_bru_content)

# Request Definitions
# (folder, filename, seq, name, method, url_path, auth_type, headers, body_type, body_content, script_post, docs_text)

requests = [
    # Auth
    ("Auth", "Vendor Login", 1, "Vendor Login", "post", "/api/vendor/login", "none", 
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"login": "vendor@example.com", "password": "password123"}, indent=2),
     """if (res.status === 200 && res.body && res.body.data && res.body.data.token) {
  bru.setEnvVar("token", res.body.data.token);
}""",
     "Vendor login using email, mobile, or subscriberId with password. Automatically saves Bearer token into {{token}}."),

    ("Auth", "OTP Verify", 2, "OTP Verify", "post", "/api/vendor/otp/verify", "none",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"mobile": "9876543210", "otp": "1234"}, indent=2),
     """if (res.status === 200 && res.body && res.body.data && res.body.data.token) {
  bru.setEnvVar("token", res.body.data.token);
}""",
     "Verify vendor login via mobile OTP. Demo OTP '1234' supported."),

    ("Auth", "OTP Resend", 3, "OTP Resend", "post", "/api/vendor/otp/resend", "none",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"mobile": "9876543210"}, indent=2),
     "", "Request a new OTP sent to vendor mobile number."),

    ("Auth", "Get Profile", 4, "Get Profile", "get", "/api/vendor/me", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Retrieve authenticated vendor profile details."),

    ("Auth", "Change Password", 5, "Change Password", "post", "/api/vendor/password/change", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"current_password": "password123", "new_password": "newpassword123", "new_password_confirmation": "newpassword123"}, indent=2),
     "", "Update vendor account password."),

    ("Auth", "Forgot Password", 6, "Forgot Password", "post", "/api/vendor/password/forgot", "none",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"mobile": "9876543210"}, indent=2),
     "", "Initiate password reset via OTP for email or mobile."),

    ("Auth", "Vendor Logout", 7, "Vendor Logout", "post", "/api/vendor/logout", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Revoke current Sanctum access token and log out."),

    # Dashboard
    ("Dashboard", "Get Dashboard", 1, "Get Dashboard", "get", "/api/vendor/dashboard", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Fetch vendor dashboard metrics (booking totals, earnings, online/offline riders, active coupons)."),

    # Business
    ("Business", "Get Business Info", 1, "Get Business Info", "get", "/api/vendor/business", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Fetch vendor business details, location, GST, and assigned pincodes."),

    ("Business", "Update Business Info", 2, "Update Business Info", "post", "/api/vendor/business/update", "bearer",
     {"Accept": "application/json"}, "multipart-form",
     """name: My Vendor Business
email: vendor@example.com
mobile: 9876543210
location: Downtown Center
gst: GSTIN987654321
image: @file(/path/to/business_logo.png)""",
     "", "Update vendor business profile and logo image."),

    ("Business", "Get Work Description", 3, "Get Work Description", "get", "/api/vendor/work-description", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Retrieve service base prices and tier pricing breakdown."),

    ("Business", "Update Work Description", 4, "Update Work Description", "post", "/api/vendor/work-description/update", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({
         "description": "Express logistics and parcel delivery",
         "biketaxi_price": 30,
         "pickup_price": 50,
         "buy_price": 40,
         "auto_price": 60,
         "cab_price": 100,
         "bt_price1": 10, "bt_price2": 15, "bt_price3": 20, "bt_price4": 25
     }, indent=2),
     "", "Update service pricing and business description."),

    # Bookings
    ("Bookings", "Today Bookings", 1, "Today Bookings", "get", "/api/vendor/bookings/today", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Fetch vendor's bookings for current date."),

    ("Bookings", "Incomplete Bookings", 2, "Incomplete Bookings", "get", "/api/vendor/bookings/incomplete", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Fetch pending (status 0) and in-progress (status 1) bookings."),

    ("Bookings", "All Bookings", 3, "All Bookings", "get", "/api/vendor/bookings", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "List all vendor bookings with status, date, search, and pagination query params."),

    ("Bookings", "Booking Details", 4, "Booking Details", "get", "/api/vendor/bookings/:id", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Retrieve full booking and payment details by booking ID."),

    ("Bookings", "Accept Booking", 5, "Accept Booking", "post", "/api/vendor/bookings/:id/accept", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Vendor accepts an incoming booking order."),

    ("Bookings", "Reject Booking", 6, "Reject Booking", "post", "/api/vendor/bookings/:id/reject", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"reason": "Service area unavailable at this time"}, indent=2),
     "", "Reject or cancel a booking with optional reason."),

    ("Bookings", "Update Booking Status", 7, "Update Booking Status", "post", "/api/vendor/bookings/:id/status", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"status": 2, "reason": "Completed successfully"}, indent=2),
     "", "Update booking status (0: Pending, 1: In Progress, 2: Completed, 3: Cancelled)."),

    ("Bookings", "Assign Rider", 8, "Assign Rider", "post", "/api/vendor/bookings/:id/assign-rider", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"driver_id": 1}, indent=2),
     "", "Assign a specific vendor rider to a booking."),

    # Riders
    ("Riders", "List Riders", 1, "List Riders", "get", "/api/vendor/riders", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "List riders/drivers belonging to vendor."),

    ("Riders", "Create Rider", 2, "Create Rider", "post", "/api/vendor/riders", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({
         "name": "Rider John",
         "mobile": "9876500001",
         "email": "riderjohn@example.com",
         "password": "password123",
         "pincode": ["600001"],
         "vehicleNo": "TN01AB1234",
         "vehicleModelNo": "Honda Activa 6G",
         "aadharNo": "123456789012",
         "drivingLicence": "TN0120230001234",
         "rcbook": "RC987654321"
     }, indent=2),
     "", "Create a new rider account under vendor."),

    ("Riders", "Rider Details", 3, "Rider Details", "get", "/api/vendor/riders/:id", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get rider profile details by ID."),

    ("Riders", "Update Rider", 4, "Update Rider", "put", "/api/vendor/riders/:id", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({
         "name": "Rider John Updated",
         "vehicleNo": "TN01AB9999",
         "location": "North Zone"
     }, indent=2),
     "", "Update rider info."),

    ("Riders", "Delete Rider", 5, "Delete Rider", "delete", "/api/vendor/riders/:id", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Delete a rider from vendor account."),

    # Coupons
    ("Coupons", "Active Coupons", 1, "Active Coupons", "get", "/api/vendor/coupons/active", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get list of active promotional coupons available for vendor's pincodes."),

    # Payments
    ("Payments", "List Payments", 1, "List Payments", "get", "/api/vendor/payments", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "List payment transactions for vendor's bookings."),

    ("Payments", "Payment Details", 2, "Payment Details", "get", "/api/vendor/payments/:id", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get detailed breakdown of a single payment record."),

    # Bank Details
    ("Bank Details", "Get Bank Details", 1, "Get Bank Details", "get", "/api/vendor/bank-details", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Retrieve vendor bank account number, IFSC code, and statement URL."),

    ("Bank Details", "Update Bank Details", 2, "Update Bank Details", "post", "/api/vendor/bank-details/update", "bearer",
     {"Accept": "application/json"}, "multipart-form",
     """bank_account_number: 918273645019
ifsc_code: SBIN0001234
account_type: Current
bankstatement: @file(/path/to/bank_statement.pdf)""",
     "", "Update bank account details and upload bank statement file."),

    # Documents
    ("Documents", "List Documents", 1, "List Documents", "get", "/api/vendor/documents", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get URLs of all uploaded vendor verification documents (Aadhaar, PAN, Bank Statement, QR, Video)."),

    ("Documents", "Upload Document", 2, "Upload Document", "post", "/api/vendor/documents", "bearer",
     {"Accept": "application/json"}, "multipart-form",
     """document_type: aadhar_front
document_file: @file(/path/to/aadhar_front.jpg)
aadhar_no: 123456789012""",
     "", "Upload vendor verification document file (allowed types: aadhar_front, aadhar_back, pan_card, bank_statement, customer_document, qr, video, profile)."),

    ("Documents", "Delete Document", 3, "Delete Document", "delete", "/api/vendor/documents/:type", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Remove a specific uploaded vendor document by type."),

    # Notifications
    ("Notifications", "List Notifications", 1, "List Notifications", "get", "/api/vendor/notifications", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "List push notifications for vendor."),

    ("Notifications", "Mark Notification Read", 2, "Mark Notification Read", "post", "/api/vendor/notifications/:id/read", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Mark single notification as read."),

    ("Notifications", "Mark All Notifications Read", 3, "Mark All Notifications Read", "post", "/api/vendor/notifications/read-all", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Mark all vendor notifications as read."),

    # Reports
    ("Reports", "Vendor Reports", 1, "Vendor Reports", "get", "/api/vendor/reports", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get vendor analytics and category revenue breakdown (period query param: today, yesterday, this_week, this_month, custom)."),

    # Settings
    ("Settings", "Get Settings", 1, "Get Settings", "get", "/api/vendor/settings", "bearer",
     {"Accept": "application/json"}, "none", "", "",
     "Get vendor app notification and alert settings."),

    ("Settings", "Update Settings", 2, "Update Settings", "post", "/api/vendor/settings/update", "bearer",
     {"Content-Type": "application/json", "Accept": "application/json"}, "json",
     json.dumps({"notification_settings": {"push_notifications": True, "email_alerts": True, "sms_alerts": False, "new_booking_sound": True}}, indent=2),
     "", "Update vendor notification preference settings."),

    ("Settings", "Support Information", 3, "Support Information", "get", "/api/vendor/info/support", "none",
     {"Accept": "application/json"}, "none", "", "",
     "Get support email, helpline number, and contact details."),

    ("Settings", "Terms and Conditions", 4, "Terms and Conditions", "get", "/api/vendor/info/terms", "none",
     {"Accept": "application/json"}, "none", "", "",
     "Get vendor terms and conditions."),

    ("Settings", "Privacy Policy", 5, "Privacy Policy", "get", "/api/vendor/info/privacy", "none",
     {"Accept": "application/json"}, "none", "", "",
     "Get vendor privacy policy."),

    ("Settings", "About App", 6, "About App", "get", "/api/vendor/info/about", "none",
     {"Accept": "application/json"}, "none", "", "",
     "Get vendor application version and company info.")
]

for req in requests:
    folder, filename, seq, req_name, method, url_path, auth_type, headers, body_type, body_content, script_post, docs_text = req
    f_dir = os.path.join(coll_dir, folder)
    os.makedirs(f_dir, exist_ok=True)
    
    lines = []
    lines.append("meta {")
    lines.append(f"  name: {req_name}")
    lines.append("  type: http")
    lines.append(f"  seq: {seq}")
    lines.append("}")
    lines.append("")
    
    # URL construction
    full_url = "{{baseUrl}}" + url_path
    lines.append(f"{method} {{")
    lines.append(f"  url: {full_url}")
    lines.append(f"  body: {body_type}")
    lines.append(f"  auth: {auth_type}")
    lines.append("}")
    lines.append("")
    
    if auth_type == "bearer":
        lines.append("auth:bearer {")
        lines.append("  token: {{token}}")
        lines.append("}")
        lines.append("")
        
    if headers:
        lines.append("headers {")
        for k, v in headers.items():
            lines.append(f"  {k}: {v}")
        lines.append("}")
        lines.append("")
        
    if body_type == "json" and body_content:
        lines.append("body:json {")
        lines.append(body_content)
        lines.append("}")
        lines.append("")
    elif body_type == "multipart-form" and body_content:
        lines.append("body:multipart-form {")
        for bline in body_content.split("\n"):
            lines.append(f"  {bline}")
        lines.append("}")
        lines.append("")
        
    if script_post:
        lines.append("script:post-response {")
        for sline in script_post.split("\n"):
            lines.append(f"  {sline}")
        lines.append("}")
        lines.append("")

    lines.append("docs {")
    lines.append(f"  {docs_text}")
    lines.append("}")
    lines.append("")

    bru_file_path = os.path.join(f_dir, f"{filename}.bru")
    with open(bru_file_path, "w") as f:
        f.write("\n".join(lines))

print(f"Generated {len(requests)} Bruno request files across {len(set(r[0] for r in requests))} modules.")
