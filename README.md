<div align="center">
  <img src="https://images.squarespace-cdn.com/content/v1/607861b10c0e3b4816f56581/3eea3ca7-58ca-402d-9edf-b8e9e72eca3c/lightning.png?format=300w" alt="Photon Commerce">

  <h1>BILL OF LADING</h1>
  <p><strong>Extract structured data from bills of lading with 99.9%+ accuracy</strong></p>
  <p><strong>Powered by <a href="https://www.photoncommerce.com">Photon Commerce</a> — Managed AI Agents with data verification and validation</strong></p>

  <p>
    <a href="https://www.photoncommerce.com"><img src="https://img.shields.io/badge/SOC%202-Compliant-blue?style=for-the-badge" alt="SOC 2"></a>
    &nbsp;&nbsp;
    <a href="https://www.photoncommerce.com"><img src="https://img.shields.io/badge/GDPR-Attested-blue?style=for-the-badge" alt="GDPR"></a>
    &nbsp;&nbsp;
    <a href="https://www.photoncommerce.com/pricing"><img src="https://img.shields.io/badge/Accuracy-99.9%25%2B-00C853?style=for-the-badge" alt="Accuracy"></a>
    &nbsp;&nbsp;
    <a href="https://www.photoncommerce.com/platform"><img src="https://img.shields.io/badge/Languages-25%2B-FF6D00?style=for-the-badge" alt="Languages"></a>
  </p>

  [Website](https://www.photoncommerce.com) &nbsp;·&nbsp; [API Docs](https://apidocs.photoncommerce.com) &nbsp;·&nbsp; [Pricing](https://www.photoncommerce.com/pricing) &nbsp;·&nbsp; [Free Trial](https://app.photoncommerce.com) &nbsp;·&nbsp;[Try in Claude](https://claude.ai/directory/connectors/photoncommerce) &nbsp;·&nbsp; [Try in ChatGPT](https://chatgpt.com/plugins/plugin_asdk_app_696685b735588191b9f25f976cfda7b2) </div> 

---

## Overview

This repository contains ready-to-run code samples for extracting structured data from bills of lading using the **Photon PRO API**.

Submit any bill of lading — ocean, air, or multimodal — and receive a structured JSON response covering shipper/consignee details, port information, container data, and risk scores, verified to 99.9%+ accuracy by Photon's Managed Agents network of 2,300+ expert reviewers across 7+ countries.

---

## Extracted Fields

| Category | Fields |
|----------|--------|
| **Metadata** | BOL number, booking number |
| **Parties** | Shipper, consignee, notify party |
| **Vessel** | Vessel name, voyage number |
| **Routing** | Loading port, discharge port, place of receipt, place of delivery |
| **Cargo** | Marks & numbers, goods description, origin/destination service terms |
| **Totals** | Total quantity (+ UOM), total measurement (+ UOM) |
| **Containers** | Container number, seal number, type & size, quantity, weight, measurement |
| **Risk** | Fraud score, risk score, anomaly score |

30+ standardised fields · Ocean, air, and multimodal BOLs supported · 25+ document languages

---

## Quick Start

```python
import requests

HEADERS = {
    "CLIENT-ID":     "YOUR_CLIENT_ID",
    "AUTHORIZATION": "apikey YOUR_USERNAME:YOUR_API_KEY",
    "PASSWORD":      "YOUR_PASSWORD",
    "SECRET-KEY":    "YOUR_SECRET_KEY",
}

# Step 1 — Submit
response = requests.post(
    "https://sandbox-api.photoncommerce.com/api/pro",
    headers=HEADERS,
    params={"doctype": "bol"},
    files={"pdf": open("bill_of_lading.pdf", "rb")},
)
photon_key = response.json()["photon_key"]

# Step 2 — Retrieve
result = requests.get(
    "https://sandbox-api.photoncommerce.com/api/v4/json",
    headers=HEADERS,
    params={"photon_key": photon_key},
).json()["data"]

print(result["BOL_Number"])    # EGLV002700640670
print(result["Shipper"])       # TRANS WAGON INT'L CO.,LTD.
print(result["Consignee"])     # STRAIGHT FORWARDING INC.
print(result["Loading_Port"])  # KAOHSIUNG, TAIWAN
```

[Get your free sandbox credentials →](https://sandbox-api.photoncommerce.com/api/v4/register)

---

## Code Examples

| Language | File | Library |
|----------|------|---------|
| ![Python](https://img.shields.io/badge/Python-3776AB?style=flat-square&logo=python&logoColor=white) | [extract_bol.py](extract_bol.py) | requests |
| ![JavaScript](https://img.shields.io/badge/Node.js-339933?style=flat-square&logo=nodedotjs&logoColor=white) | [extract_bol.js](extract_bol.js) | fetch + form-data |


Every example supports both **local file upload** and **URL-based submission**, plus optional webhook callbacks.

---

## How It Works

```
Bill of Lading In (PDF / image — ocean, air, or multimodal)
    ↓
Photon Engine  —  AI + OCR + NLP
    ↓
Managed Agents QA  —  2,300+ expert reviewers
    ↓
Structured JSON Out  —  30+ verified fields
```

### Submission

```
POST /api/pro?doctype=bol
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `doctype` | string | `bol` |
| `url` | string | URL of a publicly accessible document (alternative to file upload) |
| `webhook_url` | string | Receive a callback when extraction is complete |
| `auth_token` | string | Token to verify the webhook callback |
| `ID` | string | Your own reference ID for this submission |
| `subaccount` | string | Route to a subaccount |
| `page_start` | integer | First page to process (multi-page PDFs) |
| `page_end` | integer | Last page to process (multi-page PDFs) |

### Retrieval

```
GET /api/v4/json?photon_key=YOUR_PHOTON_KEY
```

Or pass `webhook_url` at submission and Photon will POST the result to your endpoint when ready.

---

## Processing Times

| Account Type | Turnaround |
|-------------|------------|
| Trial | Up to 24 hours (Managed Agents included) |
| Production | seconds to 24 hours (Managed Agents included) |

> **Instant Managed Agent:** To activate, contact [support@photoncommerce.com](mailto:support@photoncommerce.com). Once activated, submit to `/api/v4` instead of `/api/pro` for near-instant results.

---

## Authentication

All requests require four headers:

```
CLIENT-ID:     your-client-id
AUTHORIZATION: apikey your-username:your-api-key
PASSWORD:      your-password
SECRET-KEY:    your-secret-key
```

Get credentials: [Register a free sandbox account](https://sandbox-api.photoncommerce.com/api/v4/register) (20 free calls, no credit card required) or sign up at [app.photoncommerce.com](https://app.photoncommerce.com).

---

## Sample Response

```json
{
  "data": {
    "BOL_Number": "EGLV002700640670",
    "Booking_Number": "",
    "Shipper": "TRANS WAGON INT'L CO.,LTD.",
    "Consignee": "STRAIGHT FORWARDING INC.",
    "Notify_Party": "STRAIGHT FORWARDING INC.",
    "Vessel": "EVER STEADY",
    "Voyage": "0907-066E",
    "Loading_Port": "KAOHSIUNG, TAIWAN",
    "Discharge_Port": "LOS ANGELES, CA",
    "Receipt_Address": "TAICHUNG, TAIWAN",
    "Delivery_Address": "LOS ANGELES, CA",
    "Marks_and_Numbers": "TCNU2203208",
    "Goods_Description": "THESE ARE FOR RELIGIOUS USE ONLY. THEY WILL BE USED IN THE BUDDHIST TEMPLE AND NOT FOR RESALE. RELIGIOUS RITES GOODS\nHS CODE 3406.00",
    "Origin_Service_Terms": "O",
    "Destination_Service_Terms": "O",
    "Total_Quantity": 1830,
    "Total_Quantity_UOM": "PACKAGES",
    "Total_Measurement": 62.18,
    "Total_Measurement_UOM": "CBM",
    "Containers": [
      {
        "Container_Number": "TCNU2203208",
        "Seal_Number": "EMCGNE1746",
        "Type_and_Size": "40H",
        "Quantity": 1830,
        "Quantity_UOM": "PACKAGES",
        "Weight": 24503.32,
        "Weight_UOM": "KGS",
        "Measurement": 62.18,
        "Measurement_UOM": "CBM"
      }
    ],
    "Fraud_Score": 5,
    "Risk_Score": 5,
    "Anomaly_Score": 5,
    "photon_key": "data/johndoe@abc.com/2022-04-27/21-43-45-636324_bol.json"
  },
  "message": "success",
  "status": "success"
}
```

---

## Other Document Types

| Repo | Document |
|------|----------|
| [`photon-invoice`](https://github.com/Photon-Commerce/photon-invoice) | Invoices |
| [`photon-receipt`](https://github.com/Photon-Commerce/photon-receipt) | Receipts |
| [`photon-statement`](https://github.com/Photon-Commerce/photon-statement) | Bank & card statements |

---

## Links

| | |
|-|-|
| **Try in Claude** | [Photon Commerce on Claude](https://claude.ai/directory/connectors/photoncommerce) |
| **Try in ChatGPT** | [Photon Commerce on ChatGPT](https://chatgpt.com/plugins/plugin_asdk_app_696685b735588191b9f25f976cfda7b2) |
| **API Docs** | [apidocs.photoncommerce.com](https://apidocs.photoncommerce.com) |
| **Free Trial** | [app.photoncommerce.com](https://app.photoncommerce.com) |
| **Pricing** | [photoncommerce.com/pricing](https://www.photoncommerce.com/pricing) |
| **Support** | [support@photoncommerce.com](mailto:support@photoncommerce.com) |
| **Enterprise** | [developers@photoncommerce.com](mailto:developers@photoncommerce.com) |
