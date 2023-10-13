# h2o-work/laravel-permission
为laravel-permission添加按文件进行权限操作及回滚

## 安装及使用

1. 原版功能
   
   请查看 [原始文档](https://docs.spatie.be/laravel-permission/v2/introduction/) 进行基础安装和使用的介绍

2. 添加功能使用

   1. 执行 permission:make 增加迁移记录

      此功能会查找应用了相应权限中间件的routes创建 CREATE 类型的迁移记录
   2. 执行 permission:migrate 进行迁移
   3. 执行 permission:rollback 进行回滚