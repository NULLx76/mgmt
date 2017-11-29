import platform
import subprocess
import json

if platform.system() == "Linux" and "Ubuntu" in subprocess.check_output(["lsb_release", "-is"]).decode():
    import ubuntu_apt
    pkgs = ubuntu_apt.get_update_packages()
    updates = len(pkgs)
    security_updates = len([x for x in pkgs if x.get('security')])
    print(json.dumps(pkgs))

else:
    print("Sorry this platform is not supported")


