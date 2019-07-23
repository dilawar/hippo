Monday 22 July 2019 12:39:44 PM IST

- Database management is being migrated to codeigniter database class. See
  https://www.codeigniter.com/user_guide/database/connecting.html and
  `application/config/database.php` for more details.

Tuesday 23 July 2019 01:58:13 PM IST

- php is not storing images to `/tmp` folder. May be systemd is creating private
  temp `/tmp/systemd-***/tmp` etc. To disable this [fllow steps mentioned on
  SO](https://stackoverflow.com/questions/30444914/php-has-its-own-tmp-in-tmp-systemd-private-nabcde-tmp-when-accessed-through-ng)
