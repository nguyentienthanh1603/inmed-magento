# Deploys the given git project to the deploy directory.
git "/data/www" do
  repository "https://github.com/sgoodwin10/opsworks-test.git"
  revision "production"
  action :sync
  user "root"
  group "root"
end