/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.9
**/
/**
** #####################################
** GOODIES
**/
if(!String.prototype.trim){
    String.prototype.trim=function(){return this.replace(/^\s+|\s+$/g, '');};
}
/**
**
**
** @retval boolean
** @returns true if email-address is acceptable
**/
function verifyEmailAdress(sEmail)
{
    return(sEmail.match(/^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@((?!-))(xn--)?[a-z0-9][a-z0-9-_]{0,61}[a-z0-9]{0,1}\.(xn--)?([a-z0-9]{1,61}|[a-z0-9-]{1,30}\.[a-z]{2,})$/i));
}
/**
** #####################################
** DOMAINS
**/
/**
**
**
** @retval boolean
** @returns true if domain name is acceptable
**/
function verifyCreateDomain(Form, bSubmit)
{
    var bOk = false;
    
    Form.sdomain.value = Form.sdomain.value.trim();
    
    if(0==Form.sdomain.value.length) alert('Please enter a domain name.');
    else if(!Form.sdomain.value.match(/^((?!-))(xn--)?[a-z0-9][a-z0-9-_]{0,61}[a-z0-9]{0,1}\.(xn--)?([a-z0-9]{1,61}|[a-z0-9-]{1,30}\.[a-z]{2,})$/)){
        bOk = confirm('\t'+Form.sdomain.value+'\n\nseems not to be a valid Domain name, proceed anyway?');
    }
    else bOk = true;
    
    if(bOk && bSubmit) Form.submit();
    
    return(bOk);
}
/**
**
**
** @retval boolean
** @returns true user confirmed deletion
**/
function confirmDeleteDomain(Form, sName)
{
    if(confirm('Really delete the domain\n\n\t'+sName+'\n\nand all accounts/aliases associated with it?')){
        Form.submit();
    }
    return(false); 
}
/**
** #####################################
** ACCOUNTS
**/
/**
**
**
** @retval boolean
** @returns true account and pass are ok
**/
function verifyCreateAccount(Form, sDomain)
{
    var bOk = false;
    
    Form.saccount.value = Form.saccount.value.trim();
    Form.pwd_spassword.value = Form.pwd_spassword.value.trim();
    
    if(0==Form.saccount.value.length)
    {
        alert('Please enter a user');
    }
    else if(0==Form.pwd_spassword.value.length)
    {
        alert('Please enter a password');
    }
    else if(Form.quota && 0 > parseInt(Form.quota.value))
    {
        alert('Quota cannot be negative. Use 0 for unlimited.');
    }
    else if(!verifyEmailAdress(Form.saccount.value+'@'+sDomain) && !confirm('\t"'+Form.saccount.value+'@'+sDomain+'"\n\nseems not to be a valid email-address, proceed anyway?'))
    {

    }
    else bOk = true;
    
    if(bOk) Form.submit();
    
    return(bOk);
};
/**
**
**
** @retval boolean
** @returns true user confirmed deletion
**/
function confirmDeleteAccount(Form, sName)
{
    if(confirm('Really delete the account\n\n\t'+sName)){
        Form.submit();
    }
    return(false); 
}
/**
**
**
** @retval boolean
** @returns false
**/
function toggleEditQuota(Form)
{
    Form.style.display = (Form.style.display == 'block' ? 'none' : 'block');
    return(false);
}
/**
**
**
** @retval boolean
** @returns true password not empty
**/
function confirmChangeQuota(Form)
{
    var iQuota = parseInt(Form.quota.value);
    if(0 > iQuota)
    {
        alert('Quota cannot be negative. Use 0 for unlimited.');
    }
    else Form.submit();
    return(false);
}
/**
**
**
** @retval boolean
** @returns false
**/
function toggleNewPassword(Form)
{
    Form.style.display = (Form.style.display == 'block' ? 'none' : 'block');
    return(false);
}
/**
**
**
** @retval boolean
** @returns true password not empty
**/
function confirmChangePassword(Form)
{
    Form.pwd_spassword.value = Form.pwd_spassword.value.trim();
    if(0==Form.pwd_spassword.value.length) alert('Please enter a password');
    else Form.submit();
    return(false);
}
/**
** #####################################
** ALIASES
**/
/**
**
**
** @retval boolean
** @returns true if alias email-address name is acceptable
**/
function verifyCreateAlias(Form)
{
    var bOk = false;
    
    Form.ssource.value = Form.ssource.value.trim();
    
    if(0==Form.ssource.value.length) alert('Please enter a valid email-address as alias');
    else{
        var sSrc = Form.ssource.value+'@'+Form.iiddomain.options[Form.iiddomain.selectedIndex].innerHTML;
        bOk = true;
        if(!verifyEmailAdress(sSrc)){
            bOk = confirm('Source\n\n\t"'+sSrc+'"\n\nseems not to be a valid email-address, proceed anyway?');
        }
    }
    
    if(bOk) Form.submit();
    
    return(bOk);
}
/**
**
**
** @retval boolean
** @returns true user confirmed deletion
**/
function confirmDeleteAlias(Form, sAlias)
{
    if(confirm('Really delete the alias\n\n\t'+sAlias)){
        Form.submit();
    }
    return(false); 
}
/**
** #####################################
** Redirects
**/
/**
**
**
** @retval boolean
** @returns true if alias email-address name is acceptable
**/
function verifyCreateRedirect(Form)
{
    var bOk = false;
    
    Form.ssrc.value = Form.ssrc.value.trim();
    Form.star.value   = Form.star.value.trim();
    
    if(0==Form.ssrc.value.length) alert('Please enter a valid email-address as source');
    else if(0==Form.star.value.length) alert('Please enter a valid email-address as redirect destination');
    else{
        var sSrc = Form.ssrc.value+'@'+Form.iiddomain.options[Form.iiddomain.selectedIndex].innerHTML;
        bOk = true;

        if(!verifyEmailAdress(sSrc)){
            bOk = confirm('Source\n\n\t"'+sSrc+'"\n\nseems not to be a valid email-address, proceed anyway?');
        }
        
        if(bOk && !verifyEmailAdress(Form.star.value)){
            bOk = confirm('Destination\n\n\t"'+Form.star.value+'"\n\nseems not to be a valid email-address, proceed anyway?');
        }
    }
    if(bOk) Form.submit();
    
    return(bOk);
}
/**
**
**
** @retval boolean
** @returns true user confirmed deletion
**/
function confirmDeleteRedirect(Form, sSrc, sTar)
{
    if(confirm('Really delete the redirect\n\n\t'+sSrc+'\n\nto\n\n\t'+sTar)){
        Form.submit();
    }
    return(false); 
}
