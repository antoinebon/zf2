- mongo db profiler indexes:
> use log
> db.profiler.createIndex({ "$**": "text" })
> db.profiler.createIndex({ "report.run": -1, "report.hostname": 1 })
> db.profiler.createIndex({ "db.time": -1, "report.hostname": 1 })
> db.profiler.createIndex({ "time.total": -1, "report.hostname": 1 })
> db.profiler.createIndex({ "memory.total": -1, "report.hostname": 1 })
