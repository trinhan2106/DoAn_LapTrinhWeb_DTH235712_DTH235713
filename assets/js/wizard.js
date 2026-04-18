/**
 * assets/js/wizard.js
 * Task 5.2: Tự động lưu tiến trình lập hợp đồng (Autosave Wizard)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Nhận diện Form Wizard lập Hợp đồng
    const wizardForm = document.querySelector('#wizard-form') || document.querySelector('form.wizard-form');
    if (!wizardForm) return;

    // 1. Quản trị Định danh Lưu trữ (Storage Key)
    // Tách biệt kho lưu trữ dựa trên Session và User để tránh tràn/ghi đè dữ liệu rác chéo (Cross-data overwrite)
    const userId = document.querySelector('meta[name="user_id"]')?.content 
                || document.querySelector('input[name="user_id"]')?.value 
                || 'guest_user';
    const sessionId = document.querySelector('meta[name="session_id"]')?.content 
                   || document.querySelector('input[name="session_id"]')?.value 
                   || 'guest_session';
    
    const storageKey = `wizard_${sessionId}_${userId}`;
    const formElements = wizardForm.querySelectorAll('input, select, textarea');

    // 2. Cơ chế Read & Fill (Thu hồi Data)
    const loadWizardData = () => {
        const savedDataJSON = localStorage.getItem(storageKey);
        if (savedDataJSON) {
            try {
                const parsedData = JSON.parse(savedDataJSON);
                formElements.forEach(el => {
                    if (el.name && parsedData.hasOwnProperty(el.name)) {
                        if (el.type === 'checkbox' || el.type === 'radio') {
                            el.checked = (el.value === parsedData[el.name].toString());
                        } else if (el.type !== 'file' && el.type !== 'password' && el.type !== 'hidden') {
                            el.value = parsedData[el.name];
                        }
                    }
                });
            } catch (e) {
                console.error("[Wizard Autosave] Parsing Data Exception:", e);
            }
        }
    };

    // 3. Cơ chế Backup & Write (Sao lưu Data)
    const saveWizardData = () => {
        const dataPayload = {};
        formElements.forEach(el => {
            if (el.name) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.checked) dataPayload[el.name] = el.value;
                } else if (el.type !== 'file' && el.type !== 'password' && el.type !== 'hidden') {
                    // Không lưu trữ File Binary, Password và CSRF/Hidden token
                    dataPayload[el.name] = el.value;
                }
            }
        });
        localStorage.setItem(storageKey, JSON.stringify(dataPayload));
    };

    // 4. Lắng nghe Pipeline Trigger
    ['input', 'change'].forEach(eventType => {
        wizardForm.addEventListener(eventType, (e) => {
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') {
                saveWizardData();
            }
        });
    });

    // Boot khởi động phục hồi dữ liệu khi UI load xong
    loadWizardData();

    // 5. Global API: Function đóng gói hỗ trợ dọn dẹp Storage rác
    // Hàm này sẽ được Frontend Dev gọi nội bộ sau khi API Submit Server HTTP(200) Success.
    window.clearWizardData = function() {
        localStorage.removeItem(storageKey);
        console.log(`[Wizard Autosave] Cleaned footprint for: ${storageKey}`);
    };
});
