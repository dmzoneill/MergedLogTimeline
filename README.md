# MergedLogTimeline

## Usage
```
 Usage: ./logs.php 

  -d /path  Directory to scan
  -b 20     loglines minutes before
  -a 10     loglines minutes after
  -s "rpc"  a search string
  -o 7      ignore files older than X days
  -t 5      number of time lines to build based upon match list
  -w file   start web server [csv.file]
```

## Example

```
oneild5@XXXXXX:~/loganalyzer$ ./logs.php -d /auto/cores/c12869209/bundle_XXXXXX_2018-11-30_13-09-40/ -b 60 -a 10 -s "Async rpc taking too long" -t 5 -o 21

  ==========  GENERAL INFO  ==========
  GENERATED_ON=Fri Nov 30 06:26:04 EST 2018
  GENERATED_EPOCH_TIME=1543577164
  TIME_ZONE=US/Eastern
  VERSION=Data Domain OS 6.0.2.20-587212
  SYSTEM_SERIALNO=XXXXXXXXXXXXXXX
  CHASSIS_SERIALNO=XXXXXXXXXXXXXXXXXXXX
  MODEL_NO=DD9300
  HW_REVISION=1
  DATA_ENCRYPTION_SUPPORTED=Yes
  SSD_SHELF_PRESENT=NO
  HOSTNAME=XXXXXXXXXXXXXXXXXXXXXXX
  LOCATION=XXXXXXXXXXXXXXXXXXXXX
  ADMIN_EMAIL=XXXXXXXXXXXXXXXXXXXXX
  HA_ENABLED=false
  UPTIME= 06:26:08 up 253 days, 22:57,  1 user,  load average: 30.86, 31.94, 32.55
  Filesystem has been up   0 days, 02:19.

  Bundle size: 33G

  Iterating directory /auto/cores/c12869209/bundle_XXXXXX_2018-11-30_13-09-40
  Discovered 276 files modified in the last 21 days
  Looking for "Async rpc taking too long" strings in /auto/cores/c12869209/bundle_XXXXXX_2018-11-30_13-09-40/ddr/var/log/debug
  Found 6 matches276] /auto/cores/c12869209/bundle_XXXXXX_2018-11-30_13-09-40/ddr/var/log/debug

  Sorting match strings by time

  1] 11/30 03:38:47.215 (tid 0x4bff810): ddr/nfs/nfs_rpc.c: nfs_rpc_debug_cb: 299: Async rpc taking too long (> 420 secs)
  2] Nov 30 03:38:46 XXXXXX ddfs[15499]: ERROR: MSG-INTRNL-00001: PANIC: ddr/nfs/nfs_rpc.c: nfs_rpc_debug_cb: 299: Async rpc taking too long (> 420 secs)

  Building timeline around "11/30 03:38:47.215 (tid 0x4bff810): ddr/nfs/nfs_rpc.c: nfs_rpc_debug_cb: 299: Async rpc taking too long (> 420 secs)"

  Building timeline around "Nov 30 03:38:46 XXXXXX ddfs[15499]: ERROR: MSG-INTRNL-00001: PANIC: ddr/nfs/nfs_rpc.c: nfs_rpc_debug_cb: 299: Async rpc taking too long (> 420 secs)"
  Retrieving log lines for: Nov 30 03:46
  Sorting chronologically; log file merged timeline 2018-11-30_13-09-40/var
  Retrieving log lines for: Nov 30 03:46

  Timeline
  ========================================================================================================================
  ddfs.info            11/30 03:17:33.399 (tid 0x7f464f563e10): FV: Extracting Mtree (/data/col1/MLWDPR01) from scheduler time: curr=Thu Nov 29 06:19:18 2018
  ...
  sms                  11/30 03:46:55.784 (tid 0xa7893a0): starting job: 10987579 for operation: sms_system_get_date by sys-internal@webgui-6.0.2.20-587212
  sms                  11/30 03:46:56.469 (tid 0x7f3d85a8f2a0): created job: 10987581 for operation: sms_system_get_date from host ::ffff:192.168.35.95
  cifs.log             Nov 30 03:46:57 MLWDDA01 lwio: ERROR: [8300/1543567617.185221485] [lwio] New connection failed in protocol for fd = 193, address = '::ffff:10.191.7.82' with status = 0xc00000e5
  sms                  11/30 03:46:58.713 (tid 0x7f3d85a8f210): created job: 10987582 for operation: sms_elicense_get_scheme from host ::ffff:10.168.3.254

  Timeline Web
  ========================================================================================================================
  You can access the web page on http://XXXXXXXX:36499/temp.html?file=154460.0.4.csv

```

![screenshot](https://raw.githubusercontent.com/dmzoneill/MergedLogTimeline/master/eg.png)

