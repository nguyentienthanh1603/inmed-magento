# apt-get install this package if not installed.
package "nginx" do
  version "1.1.19-1"
end

# Use nginx.conf.erb with my attributes and variables to create the file below with correct permissions.
#template "/etc/nginx/nginx.conf" do
#  source "nginx.conf.erb"
#  owner "root"
#  group "root"
#  mode "644"
#end

package "php5-cli" do
  action :install
end

package "php5-fpm" do
  action :install
end

package "php5-gd" do
  action :install
end

package "php5-mysql" do
  action :install
end

package "php-apc" do
  action :install
end

package "php5-mcrypt" do
  action :install
end

package "php5-imap" do
  action :install
end

package "php5-curl" do
  action :install
  #notifies :reload, 'service[php5-fpm]'
end

package "sendmail" do
  action :install
end


# create nginx server block file
template "/etc/nginx/sites-available/inmed" do
  source "inmed.erb"
  owner "root"
  group "root"
  mode 00755
end

# enable the server block we just created
link "/etc/nginx/sites-available/inmed" do
  to "/etc/nginx/sites-enabled/inmed"
end



# Starts nginx.
# Configures nginx to start at boot.
service "nginx" do
  supports :restart => true, :reload => true
  action [:start, :enable]
end
