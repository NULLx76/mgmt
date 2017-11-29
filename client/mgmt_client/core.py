import platform
import subprocess

if platform.system() == "Linux" and "Ubuntu" in subprocess.check_output(["lsb_release", "-is"]).decode():
    import ubuntu_apt
    print(ubuntu_apt.get_update_packages())



