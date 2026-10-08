# Extract structured data from bills of lading using the Photon Commerce API.
#
# Submits a bill of lading (PDF or image) and returns shipper/consignee details,
# port routing, container data, and risk scores including BOL number, vessel,
# voyage, loading/discharge ports, and full container specifications.
#
# Processing times (Managed Agents):
#   Trial accounts:  up to 24 hours
#   Production:      5 minutes to 24 hours
#
# AI extraction (seconds, no Managed Agents):
#   Contact support@photoncommerce.com to activate.
#   Once active, submit to /api/v4 instead of /api/pro.
#
# Requires: pip install requests
#
# Docs:    https://apidocs.photoncommerce.com
# Sandbox: https://sandbox-api.photoncommerce.com/api/v4/register (20 free calls)

import time
import requests

CLIENT_ID  = "YOUR_CLIENT_ID"
USERNAME   = "YOUR_USERNAME"
API_KEY    = "YOUR_API_KEY"
PASSWORD   = "YOUR_PASSWORD"
SECRET_KEY = "YOUR_SECRET_KEY"

# Sandbox: https://sandbox-api.photoncommerce.com  (20 free calls, no card needed)
# Production: https://api.photoncommerce.com
BASE_URL = "https://sandbox-api.photoncommerce.com"

HEADERS = {
    "CLIENT-ID":     CLIENT_ID,
    "AUTHORIZATION": f"apikey {USERNAME}:{API_KEY}",
    "PASSWORD":      PASSWORD,
    "SECRET-KEY":    SECRET_KEY,
}


def submit_bol(file_path=None, url=None, webhook_url=None, auth_token=None,
               id=None, subaccount=None, page_start=None, page_end=None):
    if not file_path and not url:
        raise ValueError("Provide either file_path or url.")

    params = {"doctype": "bol"}
    if url:        params["url"]         = url
    if webhook_url: params["webhook_url"] = webhook_url
    if auth_token:  params["auth_token"]  = auth_token
    if id:          params["ID"]          = id
    if subaccount:  params["subaccount"]  = subaccount
    if page_start:  params["page_start"]  = page_start
    if page_end:    params["page_end"]    = page_end

    # For AI extraction (seconds), replace /api/pro with /api/v4 — contact support@photoncommerce.com to activate.
    if file_path:
        with open(file_path, "rb") as f:
            response = requests.post(f"{BASE_URL}/api/pro", headers=HEADERS,
                                     params=params, files={"pdf": f})
    else:
        response = requests.post(f"{BASE_URL}/api/pro", headers=HEADERS, params=params)

    response.raise_for_status()
    return response.json()["photon_key"]


def fetch_result(photon_key):
    response = requests.get(f"{BASE_URL}/api/v4/json", headers=HEADERS,
                            params={"photon_key": photon_key})
    response.raise_for_status()
    return response.json().get("data", {})


def wait_for_result(photon_key, poll_interval=20, timeout=3600):
    deadline = time.time() + timeout
    while time.time() < deadline:
        result = fetch_result(photon_key)
        status = result.get("Status", "")
        if status and status not in ("pending", "processing"):
            return result
        print(f"  Status: {status or 'pending'} — retrying in {poll_interval}s...")
        time.sleep(poll_interval)
    raise TimeoutError(f"Extraction not complete after {timeout}s")


if __name__ == "__main__":
    # --- Option A: submit from a local file ---
    photon_key = submit_bol(file_path="bill_of_lading.pdf")

    # --- Option B: submit via a publicly accessible URL ---
    # photon_key = submit_bol(url="https://example.com/bill_of_lading.pdf")

    print(f"Submitted. photon_key: {photon_key}")
    print("Waiting for extraction to complete...")

    result = wait_for_result(photon_key)

    print("\n--- Bill of Lading Data ---")
    print("BOL Number:         ", result.get("BOL_Number"))
    print("Booking Number:     ", result.get("Booking_Number"))
    print("Shipper:            ", result.get("Shipper"))
    print("Consignee:          ", result.get("Consignee"))
    print("Notify Party:       ", result.get("Notify_Party"))
    print("Vessel:             ", result.get("Vessel"))
    print("Voyage:             ", result.get("Voyage"))
    print("Loading Port:       ", result.get("Loading_Port"))
    print("Discharge Port:     ", result.get("Discharge_Port"))
    print("Place of Receipt:   ", result.get("Receipt_Address"))
    print("Place of Delivery:  ", result.get("Delivery_Address"))
    print("Total Quantity:     ", result.get("Total_Quantity"), result.get("Total_Quantity_UOM"))
    print("Total Measurement:  ", result.get("Total_Measurement"), result.get("Total_Measurement_UOM"))
    print("Fraud Score:        ", result.get("Fraud_Score"))
    print("Risk Score:         ", result.get("Risk_Score"))
    print("Anomaly Score:      ", result.get("Anomaly_Score"))

    containers = result.get("Containers", [])
    if containers:
        print(f"\n--- Containers ({len(containers)}) ---")
        for c in containers:
            print(f"  {c.get('Container_Number')}  Seal: {c.get('Seal_Number')}  "
                  f"Type: {c.get('Type_and_Size')}  "
                  f"{c.get('Weight')} {c.get('Weight_UOM')}  "
                  f"{c.get('Measurement')} {c.get('Measurement_UOM')}")
