set :stages, %w(deptest testcv sites)
require 'capistrano/ext/multistage'
load 'deploy' if respond_to?(:namespace) # cap2 differentiator
Dir['vendor/plugins/*/recipes/*.rb'].each { |plugin| load(plugin) }
#Added for railsless deploy
require 'rubygems'
require 'railsless-deploy'
load    'config/deploy'

require 'net/https'
require 'uri'

default_run_options[:pty] = true

namespace :update do
	desc <<-DESC
		Updates Wordpress Files including themes and plugins.
	DESC
	task :start do
		run "cd #{deploy_to}; git pull origin master"
	end
end
