import platform
import subprocess

if platform.system() == "Linux" and subprocess.check_output(["lsb_release", "-is"]) == "Ubuntu":
    import ubuntu_apt
    print(ubuntu_apt.get_update_packages())



