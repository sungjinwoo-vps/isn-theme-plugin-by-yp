# Windows to iMac SSH Guide

Use the verified SSH alias:

```powershell
ssh -o BatchMode=yes yash-imac
```

Open the WordPress tunnel from Windows:

```powershell
ssh -N -L 8888:127.0.0.1:8888 yash-imac
```

Then open:

```text
http://localhost:8888
```

Do not copy private SSH keys or pass account passwords through commands.

