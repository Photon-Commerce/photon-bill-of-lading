/**
 * Extract structured data from bills of lading using the Photon Commerce API.
 *
 * Submits a bill of lading (PDF or image) and returns shipper/consignee details,
 * port routing, container data, and risk scores including BOL number, vessel,
 * voyage, loading/discharge ports, and full container specifications.
 *
 * Processing times (Managed Agents):
 *   Trial accounts:  up to 24 hours
 *   Production:      5 minutes to 24 hours
 *
 * AI extraction (seconds, no Managed Agents):
 *   Contact support@photoncommerce.com to activate.
 *   Once active, submit to /api/v4 instead of /api/pro.
 *
 * Requires: npm install form-data node-fetch   (Node.js < 18)
 *           Node.js 18+ has fetch built-in; remove the node-fetch import.
 *
 * Docs:    https://apidocs.photoncommerce.com
 * Sandbox: https://sandbox-api.photoncommerce.com/api/v4/register (20 free calls)
 */

import FormData from "form-data";
import fetch    from "node-fetch";
import fs       from "fs";

const CLIENT_ID  = "YOUR_CLIENT_ID";
const USERNAME   = "YOUR_USERNAME";
const API_KEY    = "YOUR_API_KEY";
const PASSWORD   = "YOUR_PASSWORD";
const SECRET_KEY = "YOUR_SECRET_KEY";

// Sandbox: https://sandbox-api.photoncommerce.com  (20 free calls, no card needed)
// Production: https://api.photoncommerce.com
const BASE_URL = "https://sandbox-api.photoncommerce.com";

const AUTH_HEADERS = {
  "CLIENT-ID":     CLIENT_ID,
  "AUTHORIZATION": `apikey ${USERNAME}:${API_KEY}`,
  "PASSWORD":      PASSWORD,
  "SECRET-KEY":    SECRET_KEY,
};

async function submitBol({ filePath, url, webhookUrl, authToken, id, subaccount, pageStart, pageEnd } = {}) {
  if (!filePath && !url) throw new Error("Provide either filePath or url.");

  const params = new URLSearchParams({ doctype: "bol" });
  if (url)        params.set("url",         url);
  if (webhookUrl) params.set("webhook_url", webhookUrl);
  if (authToken)  params.set("auth_token",  authToken);
  if (id)         params.set("ID",          id);
  if (subaccount) params.set("subaccount",  subaccount);
  if (pageStart)  params.set("page_start",  pageStart);
  if (pageEnd)    params.set("page_end",    pageEnd);

  // For AI extraction (seconds), replace /api/pro with /api/v4 — contact support@photoncommerce.com to activate.
  const endpoint = `${BASE_URL}/api/pro?${params}`;
  let body, extraHeaders = {};

  if (filePath) {
    const form = new FormData();
    form.append("pdf", fs.createReadStream(filePath));
    body = form;
    extraHeaders = form.getHeaders();
  }

  const res = await fetch(endpoint, { method: "POST", headers: { ...AUTH_HEADERS, ...extraHeaders }, body });
  if (!res.ok) throw new Error(`Submit failed: ${res.status} ${await res.text()}`);
  const data = await res.json();
  return data.photon_key;
}

async function fetchResult(photonKey) {
  const res = await fetch(`${BASE_URL}/api/v4/json?photon_key=${encodeURIComponent(photonKey)}`,
    { headers: AUTH_HEADERS });
  if (!res.ok) throw new Error(`Fetch failed: ${res.status}`);
  const data = await res.json();
  return data.data ?? {};
}

async function waitForResult(photonKey, { pollInterval = 20000, timeout = 3_600_000 } = {}) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const result = await fetchResult(photonKey);
    const status = result.Status ?? "";
    if (status && status !== "pending" && status !== "processing") return result;
    console.log(`  Status: ${status || "pending"} — retrying in ${pollInterval / 1000}s...`);
    await new Promise(r => setTimeout(r, pollInterval));
  }
  throw new Error(`Extraction not complete after ${timeout / 1000}s`);
}

// --- Option A: submit from a local file ---
const photonKey = await submitBol({ filePath: "bill_of_lading.pdf" });

// --- Option B: submit via a publicly accessible URL ---
// const photonKey = await submitBol({ url: "https://example.com/bill_of_lading.pdf" });

console.log(`Submitted. photon_key: ${photonKey}`);
console.log("Waiting for extraction to complete...");

const result = await waitForResult(photonKey);

console.log("\n--- Bill of Lading Data ---");
console.log("BOL Number:        ", result.BOL_Number);
console.log("Booking Number:    ", result.Booking_Number);
console.log("Shipper:           ", result.Shipper);
console.log("Consignee:         ", result.Consignee);
console.log("Notify Party:      ", result.Notify_Party);
console.log("Vessel:            ", result.Vessel);
console.log("Voyage:            ", result.Voyage);
console.log("Loading Port:      ", result.Loading_Port);
console.log("Discharge Port:    ", result.Discharge_Port);
console.log("Place of Receipt:  ", result.Receipt_Address);
console.log("Place of Delivery: ", result.Delivery_Address);
console.log("Total Quantity:    ", result.Total_Quantity, result.Total_Quantity_UOM);
console.log("Total Measurement: ", result.Total_Measurement, result.Total_Measurement_UOM);
console.log("Fraud Score:       ", result.Fraud_Score);
console.log("Risk Score:        ", result.Risk_Score);
console.log("Anomaly Score:     ", result.Anomaly_Score);

const containers = result.Containers ?? [];
if (containers.length) {
  console.log(`\n--- Containers (${containers.length}) ---`);
  for (const c of containers) {
    console.log(`  ${c.Container_Number}  Seal: ${c.Seal_Number}  Type: ${c.Type_and_Size}  ${c.Weight} ${c.Weight_UOM}  ${c.Measurement} ${c.Measurement_UOM}`);
  }
}
