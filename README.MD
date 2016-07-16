## Swugpa/西南大学平均分绩点计算

### 功能
1. 西南大学校内门户模拟登录
2. 西南大学教务处模拟登录
3. 获取学生成绩、绩点（含平均成绩/绩点）

### 环境要求
> php version > 5.4

### 项目依赖
`php-curl-class/php-curl-class`

### 安装
```
composer require mohuishou/swugpa
```

### 使用说明
```
require __DIR__ . '/vendor/autoload.php';
$swugpa=new Mohuishou\Lib\Swugpa();
$swugpa->login($username,$password);
$swugpa->grade($year,$term);

```