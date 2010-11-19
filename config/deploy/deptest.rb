#The staging server host (this is where you ssh into)
role :app, "sites1.example.com"
set :application, "sites1.example.com"

#The unix/ftp user you ssh in as 
set :user, "username"

#location to deploy to
set :deploy_to, "/home/#{user}/public_html/#{application}/public/sites"



