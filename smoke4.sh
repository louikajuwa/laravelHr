#!/bin/sh
B=http://nginx
PASS=0; FAIL=0
login() { curl -s -c "$2" -o /tmp/lg.html "$B/login"; T=$(grep -o 'name="_token" value="[^"]*"' /tmp/lg.html|head -1|cut -d'"' -f4); curl -s -b "$2" -c "$2" -o /dev/null -X POST "$B/login" --data-urlencode "_token=$T" --data-urlencode "email=$1" --data-urlencode "password=password"; }
tok() { grep -o 'name="_token" value="[^"]*"' "$1"|head -1|cut -d'"' -f4; }
post() { # jar url expected field-args...
  jar=$1; url=$2; want=$3; shift 3
  code=$(curl -s -b "$jar" -o /tmp/po.html -w "%{http_code}" -X POST "$url" "$@")
  if [ "$code" = "$want" ]; then PASS=$((PASS+1)); echo "  OK  $code  $url";
  else FAIL=$((FAIL+1)); echo "  !!  $code (want $want)  $url"; grep -o '<title>[^<]*</title>' /tmp/po.html|head -1; fi
}

echo "===== EMPLOYEE WRITES ====="
login employee@hr.test /tmp/e
# Submit leave (future, SL=type 1)
curl -s -b /tmp/e -o /tmp/lc.html "$B/me/leave/create"; T=$(tok /tmp/lc.html)
S=$(date -d "+40 day" +%Y-%m-%d); E2=$(date -d "+41 day" +%Y-%m-%d)
post /tmp/e "$B/me/leave" 302 --data-urlencode "_token=$T" --data-urlencode "leave_type_id=1" --data-urlencode "start_date=$S" --data-urlencode "end_date=$E2" --data-urlencode "reason=Family event"
# Submit late arrival
curl -s -b /tmp/e -o /tmp/la.html "$B/me/late-arrivals/create"; T=$(tok /tmp/la.html)
post /tmp/e "$B/me/late-arrivals" 302 --data-urlencode "_token=$T" --data-urlencode "date=$(date +%Y-%m-%d)" --data-urlencode "reason=Traffic jam"
# Update profile
curl -s -b /tmp/e -o /tmp/pf.html "$B/me/profile"; T=$(tok /tmp/pf.html)
post /tmp/e "$B/me/profile" 302 --data-urlencode "_token=$T" --data-urlencode "_method=PUT" --data-urlencode "name=Jamie Employee" --data-urlencode "email=employee@hr.test" --data-urlencode "phone=555-0100"

echo "===== ADMIN WRITES ====="
login admin@hr.test /tmp/a
# Create employee
curl -s -b /tmp/a -o /tmp/ec.html "$B/hr/employees/create"; T=$(tok /tmp/ec.html)
post /tmp/a "$B/hr/employees" 302 --data-urlencode "_token=$T" --data-urlencode "name=Test Hire" --data-urlencode "email=hire$RANDOM@hr.test" --data-urlencode "password=password123" --data-urlencode "role=employee" --data-urlencode "department_id=1" --data-urlencode "employment_type=full_time"
# Create shift
curl -s -b /tmp/a -o /tmp/sc.html "$B/hr/shifts/create"; T=$(tok /tmp/sc.html)
post /tmp/a "$B/hr/shifts" 302 --data-urlencode "_token=$T" --data-urlencode "name=Flex $RANDOM" --data-urlencode "start_time=10:00" --data-urlencode "end_time=19:00" --data-urlencode "grace_period_minutes=10" --data-urlencode "break_minutes=60" --data-urlencode "overtime_threshold_minutes=0" --data-urlencode "working_days[]=mon" --data-urlencode "working_days[]=tue"
# Assign shift 1 to dept 1
curl -s -b /tmp/a -o /tmp/si.html "$B/hr/shifts"; T=$(tok /tmp/si.html)
post /tmp/a "$B/hr/shifts/1/assign" 302 --data-urlencode "_token=$T" --data-urlencode "department_id=1"
# Create salary structure for employee user 2 (re-version)
curl -s -b /tmp/a -o /tmp/ss.html "$B/hr/salary-structures/create"; T=$(tok /tmp/ss.html)
post /tmp/a "$B/hr/salary-structures" 302 --data-urlencode "_token=$T" --data-urlencode "user_id=2" --data-urlencode "basic_salary=45000" --data-urlencode "pay_frequency=monthly" --data-urlencode "working_days_per_month=22" --data-urlencode "overtime_rate_multiplier=1.25" --data-urlencode "late_deduction_per_minute=5" --data-urlencode "absence_deduction_mode=daily_rate" --data-urlencode "perfect_attendance_bonus=1000" --data-urlencode "effective_from=$(date +%Y-%m-%d)"
# Finalize + pay payroll 1
curl -s -b /tmp/a -o /tmp/p1.html "$B/hr/payroll/1"; T=$(tok /tmp/p1.html)
post /tmp/a "$B/hr/payroll/1/finalize" 302 --data-urlencode "_token=$T"
post /tmp/a "$B/hr/payroll/1/pay" 302 --data-urlencode "_token=$T"
# Late arrival review (find id)
curl -s -b /tmp/a -o /tmp/lar.html "$B/hr/late-arrivals"; T=$(tok /tmp/lar.html)
RID=$(grep -oE '/hr/late-arrivals/[0-9]+/review' /tmp/lar.html | head -1 | grep -oE '[0-9]+')
if [ -n "$RID" ]; then post /tmp/a "$B/hr/late-arrivals/$RID/review" 302 --data-urlencode "_token=$T" --data-urlencode "status=excused"; else echo "  --  no late report"; fi
# Reject a pending leave if any
curl -s -b /tmp/a -o /tmp/lj.html "$B/hr/leave?status=pending"; T=$(tok /tmp/lj.html)
RJ=$(grep -oE '/hr/leave/[0-9]+/reject' /tmp/lj.html | head -1 | grep -oE '[0-9]+')
if [ -n "$RJ" ]; then post /tmp/a "$B/hr/leave/$RJ/reject" 302 --data-urlencode "_token=$T" --data-urlencode "decision_note=no"; else echo "  --  no pending leave"; fi

echo "============================"
echo "PASS=$PASS FAIL=$FAIL"
