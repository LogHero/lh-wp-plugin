Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/xenial64"
  config.vm.network "private_network", ip: "192.168.1.10"
  config.vm.synced_folder "loghero/", "/var/www/html/wp-content/plugins/loghero/"
  config.vm.provision :ansible do |ansible|
    ansible.playbook = "provisioning/wordpress.yml"
  end
end
