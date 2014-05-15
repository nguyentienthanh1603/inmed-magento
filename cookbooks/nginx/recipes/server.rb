directory '/data' do
  owner "vagrant"
  group "vagrant"
  mode  0755
  recursive true
  action :create
  not_if { ::FileTest.exists?("/data/www/info.php") }
end

directory '/data/www' do
  owner "vagrant"
  group "vagrant"
  mode  0755
  recursive true
  action :create
  not_if { ::FileTest.exists?("/data/www/info.php") }
end

directory '/data/www' do
  owner "vagrant"
  group "vagrant"
  mode  0755
  recursive true
  action :create
  not_if { ::FileTest.exists?("/data/www/info.php") }
end


template "/data/www/info.php" do
  source "info.php.erb"
  owner "vagrant"
  group "vagrant"
  mode 0644
  not_if { ::FileTest.exists?("/data/www/info.php") }
end

package "nginx"  do
  action :install
end

service "nginx"  do
  supports :status => true, :restart => true, :reload => true
  action :nothing
end

template "/etc/nginx/common.conf" do
  source "common.conf.erb"
  owner "root"
  group "root"
  mode 0644
  notifies :restart, "service[nginx]"
end

template "/etc/nginx/fastcgi_params" do
  source "fastcgi_params.erb"
  owner "root"
  group "root"
  mode 0644
  notifies :restart, "service[nginx]"
end

template "/etc/nginx/php.conf" do
  source "php.conf.erb"
  owner "root"
  group "root"
  mode 0644
  notifies :restart, "service[nginx]"
end


template "/etc/nginx/nginx.conf" do
  source "nginx.conf.erb"
  owner "root"
  group "root"
  mode 0644
end

template "/etc/nginx/sites-available/inmedtech" do
  source "inmed.dev.erb"
  owner "root"
  group "root"
  mode 0644
  variables(
    :server_name => node['nginx']['server_name']
  )

end

link "/etc/nginx/sites-available/inmedtech" do
  to "/etc/nginx/sites-enabled/inmedtech"
end

template "/etc/nginx/sites-available/pmt" do
  source "pmt.dev.erb"
  owner "root"
  group "root"
  mode 0644
  variables(
    :server_name => node['nginx']['server_name']
  )

end

link "/etc/nginx/sites-available/pmt" do
  to "/etc/nginx/sites-enabled/pmt"
  notifies :restart, "service[nginx]"
end


script "remove_default" do
  interpreter "bash"
  user "root"
  cwd "/tmp"
  code <<-EOH
    #insert bash script
    sudo rm /etc/nginx/sites-available/default
  EOH
end

#cookbook_file "/tmp/nginx_ensite.sh" do
#  source "nginx_ensite.sh"
#  mode 0755
#end