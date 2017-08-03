<!-- BEGIN STATIC -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Automated System of Logging Water and Electricity Meters Data</title>

    <link rel="stylesheet" href="static/css/bootstrap_v3.css">
    <link rel="stylesheet" href="static/css/new_gui.css">

    <script src="static/js/jquery-3.2.1.js"></script>
    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.js"></script>-->
    <script src="static/js/bootstrap_v3.js"></script>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="flex-container">
            <div class="navbar-brand navbar-brand-width navbar-brand-padding flex-vcenter hidden-xs" href="#">
                <img src="static/imgs/esp.jpg" class="img-circle img-responsive">
                <h1 class="text-green">ESP-8266 based:</h1>
            </div>
            <div class="navbar-header navbar-header-width flex-vcenter text-center">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".collapse-target" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <h1 class="text-green hidden-xs">Automated Data-Logging System from Water and Electricity meters</h1>
                <h1 class="text-green visible-xs">ADLS from WEM</h1>
            </div>
        </div>
        <div class="collapse navbar-collapse collapse-target">
            <ul class="nav navbar-nav navbar-inverse side-nav">
                <li>
                    <a href="?page=main_stat" class="transition {{is_main_selected}}">
                        <span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
                        Main Stats
                    </a>
                </li>
                <li>
                    <a href="#meters" class="transition" role="button" data-target="#meters" aria-expanded="false" data-toggle="collapse" aria-controls="meters">
                        <input type="checkbox" id="checkbox" {{is_checked}}>
                        <span class="glyphicon glyphicon-menu-left pull-right transition rotate" style="padding-right: 0px;" aria-hidden="true"></span>
                        <span class="glyphicon glyphicon-dashboard transition rotate" aria-hidden="true"></span>
                        <label for="checkbox" class="fixed-label"></label>
                        Meters
                    </a>
                    <ul id="meters" class="collapse {{is_in}} list-unstyled toggle-target">
                        <li>
                            <a href="?page=water" class="transition {{ IF (page=="water") }}selected{{ END }}"><span class="glyphicon glyphicon-tint"></span>Water</a>
                        </li>
                        <li>
                            <a href="?page=electricity" class="transition {{is_electricity_selected}}"><span class="glyphicon glyphicon-flash"></span>Electricity</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#" class="transition">
                        <span class="glyphicon glyphicon-link" aria-hidden="true"></span>
                        Additional links
                    </a>
                </li>
                <li class="nav-divider"></li>
                <li>
                    <a href="#" class="transition">
                        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                        Settings
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="main">
        <div class="container-fluid">
            <!-- BEGIN MAIN_STAT -->
            <div class="row">
                <div class="col-sm-12">
                    <h1 class="text-green">Main Stat</h1>
                </div>
            </div>
            <!-- END MAIN_STAT -->
            <!-- BEGIN WATER -->
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="text-green">Water</h1>
                </div>
                <div class="col-sm-6">
                    <h1 class="text-green">Stat</h1>
                </div>
            </div>
            <!-- END WATER -->
        </div>
    </div>
</body>
</html>
<!-- END STATIC -->
