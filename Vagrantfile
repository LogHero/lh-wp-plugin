Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/xenial64"
  config.vm.provision :ansible do |ansible|
    ansible.playbook = "provisioning/wordpress.yml"
  end
end
