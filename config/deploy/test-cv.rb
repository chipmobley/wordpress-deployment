#The staging server host (this is where you ssh into)
role :app, "test.example.com"
set :application, "test.example.com"

#The unix/ftp user you ssh in as 
set :user, "username"

#location to deploy to
set :deploy_to, "/home/#{user}/public_html/sites"



