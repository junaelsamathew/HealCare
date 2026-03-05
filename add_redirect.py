import os
import sys

def modify_files(directory):
    count = 0
    
    for root, dirs, files in os.walk(directory):
        if 'backups' in root or '.git' in root or 'libs' in root:
            continue
        for file in files:
            if file.endswith('.php') and file != 'login.php' and file != 'auth_handler.php':
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'r', encoding='utf-8') as f:
                        content = f.read()
                    
                    if '!isset($_SESSION[\'logged_in\'])' in content or 'Location: login.php' in content:
                        if "$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];" not in content:
                            new_content = content.replace('header("Location: login.php");', '$_SESSION[\'redirect_after_login\'] = $_SERVER[\'REQUEST_URI\'];\n    header("Location: login.php");')
                            new_content = new_content.replace("header('Location: login.php');", '$_SESSION[\'redirect_after_login\'] = $_SERVER[\'REQUEST_URI\'];\n    header(\'Location: login.php\');')
                            
                            if new_content != content:
                                with open(filepath, 'w', encoding='utf-8') as f:
                                    f.write(new_content)
                                count += 1
                                print(f"Updated {filepath}")
                except Exception as e:
                    print(f"Error processing {filepath}: {e}")
                    
    print(f"Total files updated: {count}")

if __name__ == "__main__":
    modify_files(sys.argv[1])
