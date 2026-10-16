#!/usr/bin/env bash
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
# Requires: curl, jq
#
# Docs:    https://apidocs.photoncommerce.com
# Sandbox: https://sandbox-api.photoncommerce.com/api/v4/register (20 free calls)

CLIENT_ID="YOUR_CLIENT_ID"
USERNAME="YOUR_USERNAME"
API_KEY="YOUR_API_KEY"
PASSWORD="YOUR_PASSWORD"
SECRET_KEY="YOUR_SECRET_KEY"

# Sandbox: https://sandbox-api.photoncommerce.com  (20 free calls, no card needed)
# Production: https://api.photoncommerce.com
BASE_URL="https://sandbox-api.photoncommerce.com"

# --- Option A: submit from a local file ---
# For AI extraction (seconds), replace /api/pro with /api/v4 — contact support@photoncommerce.com to activate.
SUBMIT_RESPONSE=$(curl -s -X POST \
  "${BASE_URL}/api/pro?doctype=bol" \
  -H "CLIENT-ID: ${CLIENT_ID}" \
  -H "AUTHORIZATION: apikey ${USERNAME}:${API_KEY}" \
  -H "PASSWORD: ${PASSWORD}" \
  -H "SECRET-KEY: ${SECRET_KEY}" \
  -F "pdf=@bill_of_lading.pdf")

# --- Option B: submit via a publicly accessible URL ---
# SUBMIT_RESPONSE=$(curl -s -X POST \
#   "${BASE_URL}/api/pro?doctype=bol&url=https://example.com/bill_of_lading.pdf" \
#   -H "CLIENT-ID: ${CLIENT_ID}" \
#   -H "AUTHORIZATION: apikey ${USERNAME}:${API_KEY}" \
#   -H "PASSWORD: ${PASSWORD}" \
#   -H "SECRET-KEY: ${SECRET_KEY}")

PHOTON_KEY=$(echo "${SUBMIT_RESPONSE}" | jq -r '.photon_key')
echo "Submitted. photon_key: ${PHOTON_KEY}"
echo "Waiting for extraction to complete..."

POLL_INTERVAL=20
TIMEOUT=3600
ELAPSED=0

while [ "${ELAPSED}" -lt "${TIMEOUT}" ]; do
  RESULT=$(curl -s \
    "${BASE_URL}/api/v4/json?photon_key=${PHOTON_KEY}" \
    -H "CLIENT-ID: ${CLIENT_ID}" \
    -H "AUTHORIZATION: apikey ${USERNAME}:${API_KEY}" \
    -H "PASSWORD: ${PASSWORD}" \
    -H "SECRET-KEY: ${SECRET_KEY}")

  STATUS=$(echo "${RESULT}" | jq -r '.data.Status // ""')

  if [ -n "${STATUS}" ] && [ "${STATUS}" != "pending" ] && [ "${STATUS}" != "processing" ]; then
    break
  fi

  echo "  Status: ${STATUS:-pending} — retrying in ${POLL_INTERVAL}s..."
  sleep "${POLL_INTERVAL}"
  ELAPSED=$((ELAPSED + POLL_INTERVAL))
done

DATA=$(echo "${RESULT}" | jq '.data')

echo ""
echo "--- Bill of Lading Data ---"
echo "BOL Number:         $(echo "${DATA}" | jq -r '.BOL_Number // ""')"
echo "Booking Number:     $(echo "${DATA}" | jq -r '.Booking_Number // ""')"
echo "Shipper:            $(echo "${DATA}" | jq -r '.Shipper // ""')"
echo "Consignee:          $(echo "${DATA}" | jq -r '.Consignee // ""')"
echo "Notify Party:       $(echo "${DATA}" | jq -r '.Notify_Party // ""')"
echo "Vessel:             $(echo "${DATA}" | jq -r '.Vessel // ""')"
echo "Voyage:             $(echo "${DATA}" | jq -r '.Voyage // ""')"
echo "Loading Port:       $(echo "${DATA}" | jq -r '.Loading_Port // ""')"
echo "Discharge Port:     $(echo "${DATA}" | jq -r '.Discharge_Port // ""')"
echo "Place of Receipt:   $(echo "${DATA}" | jq -r '.Receipt_Address // ""')"
echo "Place of Delivery:  $(echo "${DATA}" | jq -r '.Delivery_Address // ""')"
echo "Total Quantity:     $(echo "${DATA}" | jq -r '"\(.Total_Quantity // "") \(.Total_Quantity_UOM // "")"')"
echo "Total Measurement:  $(echo "${DATA}" | jq -r '"\(.Total_Measurement // "") \(.Total_Measurement_UOM // "")"')"
echo "Fraud Score:        $(echo "${DATA}" | jq -r '.Fraud_Score // ""')"
echo "Risk Score:         $(echo "${DATA}" | jq -r '.Risk_Score // ""')"
echo "Anomaly Score:      $(echo "${DATA}" | jq -r '.Anomaly_Score // ""')"

CONTAINER_COUNT=$(echo "${DATA}" | jq '.Containers | length')
if [ "${CONTAINER_COUNT}" -gt 0 ]; then
  echo ""
  echo "--- Containers (${CONTAINER_COUNT}) ---"
  echo "${DATA}" | jq -r '.Containers[] | "  \(.Container_Number)  Seal: \(.Seal_Number)  Type: \(.Type_and_Size)  \(.Weight) \(.Weight_UOM)  \(.Measurement) \(.Measurement_UOM)"'
fi
