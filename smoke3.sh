#!/bin/sh
B=http://nginx
PASS=0; FAIL=0

login() { # email jar
  curl -s -c "$2" -o /tmp/lg.html "$B/login"
  T=$(grep -o 'name="_token" value="[^"]*"' /tmp/lg.html | head -1 | cut -d'"' -f4)
  curl -s -b "$2" -c "$2" -o /dev/null -X POST "$B/login" \
    --data-urlencode "_token=$T" --data-urlencode "email=$1" --data-urlencode "password=password"
}

get() { # jar url expected
  code=$(curl -s -b "$1" -o /tmp/pg.html -w "%{http_code}" "$2")
  if [ "$code" = "$3" ]; then PASS=$((PASS+1)); echo "  OK  $code  $2";
  else FAIL=$((FAIL+1)); echo "  !!  $code (want $3)  $2"; grep -o 'class="exception_title"[^<]*\|<title>[^<]*</title>' /tmp/pg.html | head -1; fi
}

tokenfrom() { grep -o 'name="_token" value="[^"]*"' "$1" | head -1 | cut -d'"' -f4; }

echo "================= ADMIN GET SWEEP ================="
login admin@hr.test /tmp/a
for u in \
  /hr/dashboard /hr/dashboard/metrics \
  /hr/employees /hr/employees/create /hr/employees/1 /hr/employees/1/edit "/hr/employees?ajax=1" \
  /hr/departments /hr/departments/create /hr/departments/1/edit \
  /hr/positions /hr/positions/create /hr/positions/1/edit \
  /hr/shifts /hr/shifts/create /hr/shifts/1/edit \
  /hr/attendance /hr/attendance/1/edit \
  /hr/attendance-reports/daily /hr/attendance-reports/weekly /hr/attendance-reports/monthly \
  /hr/leave /hr/leave-types /hr/leave-types/create /hr/leave-types/1/edit \
  /hr/late-arrivals \
  /hr/payroll /hr/payroll/1 /hr/payroll/1/payslip /hr/payroll-export \
  /hr/salary-structures /hr/salary-structures/create /hr/salary-structures/1/edit \
  /hr/audit ; do
  get /tmp/a "$B$u" 200
done

echo "================= ADMIN WRITE ACTIONS ============="
# Create department
curl -s -b /tmp/a -o /tmp/dc.html "$B/hr/departments/create"; T=$(tokenfrom /tmp/dc.html)
code=$(curl -s -b /tmp/a -o /dev/null -w "%{http_code}" -X POST "$B/hr/departments" \
  --data-urlencode "_token=$T" --data-urlencode "name=Marketing $RANDOM" --data-urlencode "code=MKT" --data-urlencode "is_active=1")
[ "$code" = "302" ] && { PASS=$((PASS+1)); echo "  OK  302  POST /hr/departments"; } || { FAIL=$((FAIL+1)); echo "  !!  $code POST /hr/departments"; }

# Approve a pending leave (if any)
curl -s -b /tmp/a -o /tmp/lv.html "$B/hr/leave?status=pending"
LID=$(grep -oE '/hr/leave/[0-9]+/approve' /tmp/lv.html | head -1 | grep -oE '[0-9]+')
if [ -n "$LID" ]; then
  T=$(tokenfrom /tmp/lv.html)
  code=$(curl -s -b /tmp/a -o /dev/null -w "%{http_code}" -X POST "$B/hr/leave/$LID/approve" --data-urlencode "_token=$T" --data-urlencode "decision_note=ok")
  [ "$code" = "302" ] && { PASS=$((PASS+1)); echo "  OK  302  POST approve leave #$LID"; } || { FAIL=$((FAIL+1)); echo "  !!  $code approve leave #$LID"; }
else echo "  --  no pending leave to approve"; fi

# Correct attendance #1
curl -s -b /tmp/a -o /tmp/ae.html "$B/hr/attendance/1/edit"; T=$(tokenfrom /tmp/ae.html)
code=$(curl -s -b /tmp/a -o /dev/null -w "%{http_code}" -X POST "$B/hr/attendance/1" \
  --data-urlencode "_token=$T" --data-urlencode "_method=PUT" \
  --data-urlencode "status=present" --data-urlencode "remarks=verified" --data-urlencode "reason=Manual test correction")
[ "$code" = "302" ] && { PASS=$((PASS+1)); echo "  OK  302  PUT correct attendance #1"; } || { FAIL=$((FAIL+1)); echo "  !!  $code correct attendance #1"; }

# Generate payroll for a fresh period (2 months ago)
PS=$(date -d "$(date +%Y-%m-01) -2 month" +%Y-%m-01 2>/dev/null || date +%Y-%m-01)
PE=$(date -d "$PS +1 month -1 day" +%Y-%m-%d 2>/dev/null || date +%Y-%m-28)
curl -s -b /tmp/a -o /tmp/pi.html "$B/hr/payroll"; T=$(tokenfrom /tmp/pi.html)
code=$(curl -s -b /tmp/a -o /dev/null -w "%{http_code}" -X POST "$B/hr/payroll/generate" \
  --data-urlencode "_token=$T" --data-urlencode "period_start=$PS" --data-urlencode "period_end=$PE")
[ "$code" = "302" ] && { PASS=$((PASS+1)); echo "  OK  302  POST generate payroll $PS..$PE"; } || { FAIL=$((FAIL+1)); echo "  !!  $code generate payroll"; }

echo "================= EMPLOYEE GET SWEEP =============="
login employee@hr.test /tmp/e
for u in /me/dashboard /me/attendance "/me/attendance?ajax=1" /me/leave /me/leave/create /me/leave-balances \
  /me/late-arrivals/create /me/payroll /me/profile ; do
  get /tmp/e "$B$u" 200
done
# Employee own payslip show + pdf
PID=$(grep -oE '/me/payroll/[0-9]+' /tmp/pg.html /tmp/me*.html 2>/dev/null | grep -oE '[0-9]+' | head -1)
curl -s -b /tmp/e -o /tmp/mpl.html "$B/me/payroll"
PID=$(grep -oE '/me/payroll/[0-9]+' /tmp/mpl.html | head -1 | grep -oE '[0-9]+')
if [ -n "$PID" ]; then get /tmp/e "$B/me/payroll/$PID" 200; get /tmp/e "$B/me/payroll/$PID/payslip" 200; fi
# Employee blocked from HR
get /tmp/e "$B/hr/payroll" 403

echo "================================================="
echo "PASS=$PASS FAIL=$FAIL"
